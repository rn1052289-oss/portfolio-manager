<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Stripe\PaymentIntentSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\Uuid;
use Stripe\PaymentIntent;

final class CreateInvoicePaymentIntentAction
{
    /**
     * 再利用できる決済状態です。
     *
     * @var array<int, string>
     */
    private const REUSABLE_PAYMENT_STATUSES = [
        Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        Payment::STATUS_REQUIRES_CONFIRMATION,
        Payment::STATUS_REQUIRES_ACTION,
        Payment::STATUS_PROCESSING,
    ];

    public function __construct(
        private readonly StripeServiceInterface $stripeService,
        private readonly PaymentIntentSynchronizer $paymentIntentSynchronizer,
    ) {}

    /**
     * 請求書用のPaymentIntentを準備します。
     *
     * 支払い途中のPaymentがすでに存在する場合は、
     * 新しく作成せず既存のPaymentIntentを再利用します。
     *
     * @return array{
     *     payment: Payment,
     *     payment_intent: PaymentIntent,
     *     reused: bool
     * }
     */
    public function execute(Invoice $invoice): array
    {
        if (! $invoice->exists) {
            throw new InvalidArgumentException(
                '保存されていない請求書は決済できません。'
            );
        }

        /*
         * 同じ請求書に対する同時リクエストを直列化し、
         * PaymentIntentの二重作成を防ぎます。
         *
         * 現在のpaymentsテーブルには決済作成中を表す
         * 予約レコードを保存できないため、Stripe作成処理まで
         * Invoiceの行ロックを維持します。
         */
        return DB::transaction(function () use ($invoice): array {
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureInvoiceIsPayable($lockedInvoice);

            $amount = (int) $lockedInvoice->total_amount;
            $currency = strtolower(
                trim((string) $lockedInvoice->currency)
            );

            $this->validatePaymentDetails(
                amount: $amount,
                currency: $currency,
            );

            /*
             * 成功済みPaymentが存在する場合は、
             * InvoiceのWebhook反映が遅れていても再決済させません。
             */
            if (
                $lockedInvoice->payments()
                    ->where(
                        'status',
                        Payment::STATUS_SUCCEEDED
                    )
                    ->exists()
            ) {
                throw new LogicException(
                    'この請求書には成功済みの決済があります。'
                );
            }

            $reusablePayment = $lockedInvoice->payments()
                ->whereIn(
                    'status',
                    self::REUSABLE_PAYMENT_STATUSES
                )
                ->latest('id')
                ->first();

            if ($reusablePayment instanceof Payment) {
                $this->ensurePaymentBelongsToInvoice(
                    payment: $reusablePayment,
                    invoice: $lockedInvoice,
                );

                /*
                 * Laravel側の状態だけを信用せず、
                 * Stripeから最新のPaymentIntentを取得します。
                 */
                $paymentIntent = $this->stripeService
                    ->retrievePaymentIntent(
                        $reusablePayment->stripe_payment_intent_id
                    );

                $this->ensurePaymentIntentMatches(
                    paymentIntent: $paymentIntent,
                    amount: $amount,
                    currency: $currency,
                    expectedPaymentIntentId: $reusablePayment->stripe_payment_intent_id,
                );

                $reusablePayment = $this->paymentIntentSynchronizer
                    ->sync(
                        payment: $reusablePayment,
                        paymentIntent: $paymentIntent,
                    );

                /*
                 * Stripe側でキャンセル済みになっていた場合だけ、
                 * 既存Paymentを再利用せず新しい決済を作成します。
                 */
                if (! $reusablePayment->isCanceled()) {
                    if ($reusablePayment->isSucceeded()) {
                        throw new LogicException(
                            'この請求書の支払いはすでに完了しています。'
                        );
                    }

                    return [
                        'payment' => $reusablePayment,
                        'payment_intent' => $paymentIntent,
                        'reused' => true,
                    ];
                }
            }

            /*
             * 決済試行回数から決定的なUUIDを生成します。
             *
             * Stripe作成後にDB保存が失敗して再実行された場合も、
             * 同じ試行番号なら同じ冪等性キーが生成されるため、
             * Stripe側でPaymentIntentが重複作成されません。
             */
            $attemptNumber = $lockedInvoice->payments()
                ->count() + 1;

            $idempotencyKey = $this->makeIdempotencyKey(
                invoice: $lockedInvoice,
                attemptNumber: $attemptNumber,
            );

            $metadata = [
                'payment_type' => 'invoice',
                'invoice_id' => (string) $lockedInvoice->getKey(),
            ];

            $description = Str::limit(
                sprintf(
                    '請求書 %s：%s',
                    $lockedInvoice->invoice_number,
                    $lockedInvoice->title,
                ),
                500,
                ''
            );

            /*
             * 金額と通貨はブラウザから受け取らず、
             * Invoiceに保存された値を使用します。
             */
            $paymentIntent = $this->stripeService
                ->createPaymentIntent(
                    amount: $amount,
                    currency: $currency,
                    description: $description,
                    metadata: $metadata,
                    idempotencyKey: $idempotencyKey,
                );

            $this->ensurePaymentIntentMatches(
                paymentIntent: $paymentIntent,
                amount: $amount,
                currency: $currency,
            );

            $failureMessage = data_get(
                $paymentIntent,
                'last_payment_error.message'
            );

            $payment = new Payment([
                'project_id' => $lockedInvoice->project_id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'idempotency_key' => $idempotencyKey,
                'amount' => (int) $paymentIntent->amount,
                'currency' => strtolower(
                    (string) $paymentIntent->currency
                ),
                'status' => (string) $paymentIntent->status,
                'description' => $description,
                'failure_message' => is_string($failureMessage)
                    ? $failureMessage
                    : null,
                'paid_at' => $paymentIntent->status
                    === Payment::STATUS_SUCCEEDED
                    ? now()
                    : null,
                'canceled_at' => $paymentIntent->status
                    === Payment::STATUS_CANCELED
                    ? now()
                    : null,
                'metadata' => $metadata,
            ]);

            /*
             * user_idは外部入力や一括代入から設定せず、
             * 請求書所有者から明示的に引き継ぎます。
             */
            $payment->user_id = $lockedInvoice->user_id;

            /*
             * payments()経由で保存し、
             * invoice_idを対象請求書へ固定します。
             */
            $lockedInvoice->payments()->save($payment);

            return [
                'payment' => $payment->refresh(),
                'payment_intent' => $paymentIntent,
                'reused' => false,
            ];
        }, 3);
    }

    /**
     * 請求書がオンライン決済可能な状態か確認します。
     */
    private function ensureInvoiceIsPayable(
        Invoice $invoice
    ): void {
        if (
            ! in_array(
                $invoice->status,
                [
                    Invoice::STATUS_SENT,
                    Invoice::STATUS_OVERDUE,
                ],
                true
            )
        ) {
            throw new LogicException(
                'この請求書は現在支払いできません。'
            );
        }
    }

    /**
     * 請求金額と通貨を検証します。
     */
    private function validatePaymentDetails(
        int $amount,
        string $currency,
    ): void {
        /*
         * 既存の案件決済と同じ金額範囲を使用します。
         */
        if ($amount < 50 || $amount > 99999999) {
            throw new InvalidArgumentException(
                '請求金額が許可された決済範囲外です。'
            );
        }

        if (
            strlen($currency) !== 3
            || ! ctype_alpha($currency)
        ) {
            throw new InvalidArgumentException(
                '請求書の通貨が正しくありません。'
            );
        }
    }

    /**
     * 再利用対象PaymentとInvoiceの所有関係を確認します。
     */
    private function ensurePaymentBelongsToInvoice(
        Payment $payment,
        Invoice $invoice,
    ): void {
        if (
            (int) $payment->invoice_id
            !== (int) $invoice->getKey()
            || (int) $payment->user_id
            !== (int) $invoice->user_id
            || (
                $payment->project_id !== null
                && (int) $payment->project_id
                !== (int) $invoice->project_id
            )
        ) {
            throw new LogicException(
                '決済と請求書の紐付けが一致しません。'
            );
        }
    }

    /**
     * StripeとInvoiceの決済情報が一致するか確認します。
     */
    private function ensurePaymentIntentMatches(
        PaymentIntent $paymentIntent,
        int $amount,
        string $currency,
        ?string $expectedPaymentIntentId = null,
    ): void {
        if (
            (
                $expectedPaymentIntentId !== null
                && $paymentIntent->id
                !== $expectedPaymentIntentId
            )
            || (int) $paymentIntent->amount !== $amount
            || strtolower((string) $paymentIntent->currency)
            !== $currency
        ) {
            throw new LogicException(
                'Stripeと請求書の決済情報が一致しません。'
            );
        }
    }

    /**
     * 請求書と決済試行回数から冪等性キーを生成します。
     */
    private function makeIdempotencyKey(
        Invoice $invoice,
        int $attemptNumber,
    ): string {
        return Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            sprintf(
                'clientwork-manager:invoice-payment:%d:attempt:%d',
                $invoice->getKey(),
                $attemptNumber,
            )
        )->toString();
    }
}
