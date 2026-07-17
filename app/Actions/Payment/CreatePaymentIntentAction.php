<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Stripe\PaymentIntent;

final class CreatePaymentIntentAction
{
    public function __construct(
        private readonly StripeServiceInterface $stripeService,
    ) {}

    /**
     * StripeにPaymentIntentを作成し、
     * Laravelのpaymentsテーブルへ保存する。
     *
     * @return array{
     *     payment: Payment,
     *     payment_intent: PaymentIntent
     * }
     */
    public function execute(
        User $user,
        Project $project,
        int $amount,
        ?string $description,
        string $idempotencyKey,
    ): array {
        /*
         * 呼び出し元のControllerで権限確認を行っていても、
         * 共通処理側でもユーザーと案件の整合性を確認する。
         *
         * 別ユーザーの案件を誤って決済対象にすることを防ぐ。
         */
        if ((int) $project->user_id !== (int) $user->getKey()) {
            throw new LogicException(
                'ユーザーと案件の所有者が一致しません。'
            );
        }

        /*
         * APIとブラウザ決済で同じ金額範囲を使用する。
         *
         * API側ではFormRequestでも検証するが、
         * Action単体でも不正な金額を拒否する。
         */
        if ($amount < 50 || $amount > 99999999) {
            throw new InvalidArgumentException(
                '決済金額が許可された範囲外です。'
            );
        }

        $currency = config('services.stripe.currency', 'jpy');

        if (! is_string($currency) || strlen($currency) !== 3) {
            throw new RuntimeException(
                'Stripeの通貨設定が正しくありません。'
            );
        }

        /*
         * 空白だけの説明文はnullとして扱う。
         */
        $normalizedDescription = is_string($description)
            ? trim($description)
            : null;

        if ($normalizedDescription === '') {
            $normalizedDescription = null;
        }

        /*
         * Stripe DashboardとLaravelのデータを
         * 対応させるためのmetadata。
         *
         * 秘密情報やカード情報は保存しない。
         */
        $metadata = [
            'user_id' => (string) $user->getKey(),
            'project_id' => (string) $project->getKey(),
            'project_title' => (string) $project->title,
        ];

        /*
         * StripeServiceInterfaceを通して、
         * StripeにPaymentIntentを作成する。
         *
         * テストではFakeStripeServiceへ差し替えられる。
         */
        $paymentIntent = $this->stripeService->createPaymentIntent(
            amount: $amount,
            currency: $currency,
            description: $normalizedDescription,
            metadata: $metadata,
            idempotencyKey: $idempotencyKey,
        );

        $failureMessage = data_get(
            $paymentIntent,
            'last_payment_error.message'
        );

        /*
         * DB保存に失敗して同じ処理を再実行しても、
         * 同じIdempotency-Keyなら既存Paymentを更新する。
         */
        $payment = DB::transaction(function () use ($user, $project, $paymentIntent, $idempotencyKey, $normalizedDescription, $metadata, $failureMessage): Payment {
            return $user->payments()->updateOrCreate(
                [
                    'idempotency_key' => $idempotencyKey,
                ],
                [
                    'project_id' => $project->getKey(),
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'amount' => (int) $paymentIntent->amount,
                    'currency' => (string) $paymentIntent->currency,
                    'status' => (string) $paymentIntent->status,
                    'description' => $normalizedDescription,
                    'failure_message' => is_string($failureMessage)
                        ? $failureMessage
                        : null,
                    'paid_at' => $paymentIntent->status === 'succeeded'
                        ? now()
                        : null,
                    'canceled_at' => $paymentIntent->status === 'canceled'
                        ? now()
                        : null,
                    'metadata' => $metadata,
                ],
            );
        }, 3);

        return [
            'payment' => $payment,
            'payment_intent' => $paymentIntent,
        ];
    }
}
