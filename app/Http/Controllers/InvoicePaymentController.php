<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payment\CreateInvoicePaymentIntentAction;
use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

final class InvoicePaymentController extends Controller
{
    /**
     * 顧客向け請求書公開ページを表示します。
     *
     * ログイン認証やInvoicePolicyは使用せず、
     * 推測困難なpublic_tokenと請求書ステータスで
     * 公開可否を判定します。
     */
    public function show(
        string $publicToken,
        StripeServiceInterface $stripeService,
    ): Response {
        $invoice = Invoice::query()
            ->where('public_token', $publicToken)
            ->firstOrFail();

        /*
         * 下書きは顧客へ正式発行されていないため、
         * 請求書の存在自体を公開せず404にします。
         */
        if ($invoice->isDraft()) {
            abort(404);
        }

        if (! in_array($invoice->status, Invoice::STATUSES, true)) {
            abort(404);
        }

        $pageState = match ($invoice->status) {
            Invoice::STATUS_SENT,
            Invoice::STATUS_OVERDUE => 'payable',

            Invoice::STATUS_PAID => 'paid',

            Invoice::STATUS_CANCELED => 'canceled',

            default => abort(404),
        };

        /*
         * Payment Element用の初期値です。
         *
         * 支払いを開始していない場合や支払い不可の場合は、
         * client_secretなどをBladeへ渡しません。
         */
        $payment = null;
        $stripeStatus = null;
        $stripeStatusLabel = null;
        $clientSecret = null;
        $stripePublicKey = null;
        $returnUrl = null;
        $canContinuePayment = false;
        $paymentSetupError = null;

        if ($pageState === 'payable') {
            /*
             * PaymentIntent作成済みの場合だけ取得します。
             *
             * GETアクセスだけでPaymentIntentを作成しないため、
             * ページ閲覧や再読み込みで決済履歴が増えません。
             */
            $payment = $invoice->payments()
                ->whereIn('status', [
                    Payment::STATUS_REQUIRES_PAYMENT_METHOD,
                    Payment::STATUS_REQUIRES_CONFIRMATION,
                    Payment::STATUS_REQUIRES_ACTION,
                    Payment::STATUS_PROCESSING,
                    Payment::STATUS_SUCCEEDED,
                ])
                ->latest('id')
                ->first();

            if ($payment instanceof Payment) {
                /*
                 * Invoiceリレーション経由で取得していても、
                 * 所有者・案件・金額・通貨を改めて確認します。
                 */
                $paymentMatchesInvoice =
                    (int) $payment->invoice_id
                    === (int) $invoice->getKey()
                    && (int) $payment->user_id
                    === (int) $invoice->user_id
                    && (
                        $payment->project_id === null
                        || (int) $payment->project_id
                        === (int) $invoice->project_id
                    )
                    && (int) $payment->amount
                    === (int) $invoice->total_amount
                    && strtolower((string) $payment->currency)
                    === strtolower((string) $invoice->currency);

                if (! $paymentMatchesInvoice) {
                    Log::critical(
                        '公開請求書と決済情報の紐付けが一致しません。',
                        [
                            'invoice_id' => $invoice->getKey(),
                            'payment_id' => $payment->getKey(),
                        ]
                    );

                    $paymentSetupError =
                        '決済情報の整合性を確認できませんでした。請求元へお問い合わせください。';
                } else {
                    try {
                        /*
                         * Laravel側の状態だけを信用せず、
                         * StripeからPaymentIntentの最新状態を取得します。
                         */
                        $paymentIntent = $stripeService
                            ->retrievePaymentIntent(
                                $payment->stripe_payment_intent_id
                            );

                        $paymentIntentMatches =
                            $paymentIntent->id
                            === $payment->stripe_payment_intent_id
                            && (int) $paymentIntent->amount
                            === (int) $invoice->total_amount
                            && strtolower(
                                (string) $paymentIntent->currency
                            ) === strtolower(
                                (string) $invoice->currency
                            );

                        if (! $paymentIntentMatches) {
                            Log::critical(
                                'Stripeと公開請求書の決済情報が一致しません。',
                                [
                                    'invoice_id' => $invoice->getKey(),
                                    'payment_id' => $payment->getKey(),
                                    'stripe_payment_intent_id' => $paymentIntent->id,
                                ]
                            );

                            $paymentSetupError =
                                '決済情報の整合性を確認できませんでした。請求元へお問い合わせください。';
                        } else {
                            $stripeStatus = (string) $paymentIntent->status;

                            $stripeStatusLabel = match ($stripeStatus) {
                                Payment::STATUS_REQUIRES_PAYMENT_METHOD => '支払い情報の入力待ち',

                                Payment::STATUS_REQUIRES_CONFIRMATION => '決済確定待ち',

                                Payment::STATUS_REQUIRES_ACTION => '本人認証待ち',

                                Payment::STATUS_PROCESSING => '決済処理中',

                                Payment::STATUS_SUCCEEDED => '入金状況を確認中',

                                Payment::STATUS_CANCELED => 'キャンセル済み',

                                default => '決済状況を確認中',
                            };

                            $canContinuePayment = in_array(
                                $stripeStatus,
                                [
                                    Payment::STATUS_REQUIRES_PAYMENT_METHOD,
                                    Payment::STATUS_REQUIRES_CONFIRMATION,
                                ],
                                true
                            );

                            if ($canContinuePayment) {
                                $retrievedClientSecret =
                                    $paymentIntent->client_secret;

                                if (
                                    ! is_string($retrievedClientSecret)
                                    || $retrievedClientSecret === ''
                                ) {
                                    Log::error(
                                        '公開請求書のPaymentIntentにclient_secretがありません。',
                                        [
                                            'invoice_id' => $invoice->getKey(),
                                            'payment_id' => $payment->getKey(),
                                        ]
                                    );

                                    $paymentSetupError =
                                        '決済フォームを準備できませんでした。画面を再読み込みしてください。';

                                    $canContinuePayment = false;
                                } else {
                                    $configuredPublicKey =
                                        config('services.stripe.key');

                                    $validPublicKey =
                                        is_string(
                                            $configuredPublicKey
                                        )
                                        && str_starts_with(
                                            $configuredPublicKey,
                                            'pk_'
                                        )
                                        && (
                                            ! app()->environment([
                                                'local',
                                                'testing',
                                            ])
                                            || str_starts_with(
                                                $configuredPublicKey,
                                                'pk_test_'
                                            )
                                        );

                                    if (! $validPublicKey) {
                                        Log::error(
                                            '公開請求書用のStripe公開鍵が正しくありません。',
                                            [
                                                'invoice_id' => $invoice->getKey(),
                                                'payment_id' => $payment->getKey(),
                                                'environment' => app()->environment(),
                                            ]
                                        );

                                        $paymentSetupError =
                                            '現在、オンライン決済を利用できません。時間を置いて再度お試しください。';

                                        $canContinuePayment = false;
                                    } else {
                                        $clientSecret =
                                            $retrievedClientSecret;

                                        $stripePublicKey =
                                            $configuredPublicKey;

                                        $returnUrl = route(
                                            'invoice-payments.result',
                                            [
                                                'publicToken' => $invoice->public_token,
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    } catch (ApiErrorException $exception) {
                        Log::error(
                            '公開請求書画面でPaymentIntentを取得できませんでした。',
                            [
                                'invoice_id' => $invoice->getKey(),
                                'payment_id' => $payment->getKey(),
                                'exception' => $exception::class,
                                'stripe_code' => $exception->getStripeCode(),
                                'stripe_request_id' => $exception->getRequestId(),
                                'http_status' => $exception->getHttpStatus(),
                            ]
                        );

                        $paymentSetupError =
                            '決済情報を取得できませんでした。時間を置いて、画面を再読み込みしてください。';
                    }
                }
            }
        }

        /*
         * 請求書ページが検索エンジンや共有端末の
         * キャッシュへ残るリスクを抑えます。
         */
        return response()
            ->view('invoice-payments.show', [
                'invoice' => $invoice,
                'pageState' => $pageState,
                'payment' => $payment,
                'stripeStatus' => $stripeStatus,
                'stripeStatusLabel' => $stripeStatusLabel,
                'clientSecret' => $clientSecret,
                'stripePublicKey' => $stripePublicKey,
                'returnUrl' => $returnUrl,
                'canContinuePayment' => $canContinuePayment,
                'paymentSetupError' => $paymentSetupError,
            ])
            ->header(
                'Cache-Control',
                'no-store, private, max-age=0'
            )
            ->header(
                'Pragma',
                'no-cache'
            )
            ->header(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive'
            );
    }

    /**
     * 顧客向け請求書のPaymentIntentを準備します。
     *
     * 金額・通貨・請求書IDなどはリクエストから受け取らず、
     * public_tokenで取得したInvoiceから決定します。
     */
    public function store(
        string $publicToken,
        CreateInvoicePaymentIntentAction $createInvoicePaymentIntentAction,
    ): RedirectResponse {
        $invoice = Invoice::query()
            ->where('public_token', $publicToken)
            ->firstOrFail();

        /*
         * 下書き請求書は存在自体を公開しません。
         */
        if ($invoice->isDraft()) {
            abort(404);
        }

        /*
         * 支払い済み・キャンセル済みの場合は、
         * PaymentIntentを作らず状態案内画面へ戻します。
         */
        if ($invoice->isPaid() || $invoice->isCanceled()) {
            return redirect()
                ->route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ]);
        }

        /*
         * 想定外のステータスでは決済処理を開始しません。
         */
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
            abort(404);
        }

        try {
            $result = $createInvoicePaymentIntentAction
                ->execute($invoice);
        } catch (InvalidArgumentException $exception) {
            Log::warning(
                '請求書決済の金額または通貨を検証できませんでした。',
                [
                    'invoice_id' => $invoice->getKey(),
                    'exception' => $exception::class,
                ]
            );

            return redirect()
                ->route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ])
                ->with(
                    'error',
                    '請求内容を確認できないため、現在はオンライン決済を利用できません。請求元へお問い合わせください。'
                );
        } catch (LogicException $exception) {
            Log::notice(
                '請求書決済を開始できない状態です。',
                [
                    'invoice_id' => $invoice->getKey(),
                    'exception' => $exception::class,
                ]
            );

            return redirect()
                ->route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ])
                ->with(
                    'error',
                    '現在の請求書状態では、新しい決済を開始できません。画面を再読み込みして状況をご確認ください。'
                );
        } catch (ApiErrorException $exception) {
            /*
             * Stripeから返された詳細やclient_secretは、
             * 顧客画面やログへそのまま出しません。
             */
            Log::error(
                '請求書決済のPaymentIntent準備に失敗しました。',
                [
                    'invoice_id' => $invoice->getKey(),
                    'exception' => $exception::class,
                    'stripe_code' => $exception->getStripeCode(),
                    'stripe_request_id' => $exception->getRequestId(),
                    'http_status' => $exception->getHttpStatus(),
                ]
            );

            return redirect()
                ->route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ])
                ->with(
                    'error',
                    '決済サービスとの通信に失敗しました。時間を置いて、もう一度お試しください。'
                );
        } catch (RuntimeException $exception) {
            Log::error(
                '請求書決済のStripe設定に問題があります。',
                [
                    'invoice_id' => $invoice->getKey(),
                    'exception' => $exception::class,
                ]
            );

            return redirect()
                ->route('invoice-payments.show', [
                    'publicToken' => $invoice->public_token,
                ])
                ->with(
                    'error',
                    '現在、オンライン決済を利用できません。時間を置いて再度お試しいただくか、請求元へお問い合わせください。'
                );
        }

        $message = $result['reused']
            ? '作成済みの決済情報を再表示します。'
            : '安全な決済フォームを準備しました。';

        return redirect()
            ->route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
            ->with('success', $message);
    }

    /**
     * Stripeの決済処理後に戻るページです。
     *
     * ブラウザがこのURLへ戻ったことだけでは、
     * 入金成功とは判断しません。
     *
     * PaymentとInvoiceの正式な更新は、
     * Stripe Webhookの処理だけが担当します。
     */
    public function result(string $publicToken): RedirectResponse
    {
        $invoice = Invoice::query()
            ->where('public_token', $publicToken)
            ->firstOrFail();

        /*
         * 下書き請求書は、決済結果ページからも公開しません。
         */
        if ($invoice->isDraft()) {
            abort(404);
        }

        if (! in_array($invoice->status, Invoice::STATUSES, true)) {
            abort(404);
        }

        /*
         * Stripeがreturn_urlへ追加する可能性がある
         * payment_intentやclient_secretなどのクエリ文字列は
         * 引き継がず、public_tokenだけを使ってリダイレクトします。
         *
         * この処理ではInvoiceをpaidへ変更しません。
         */
        return redirect()
            ->route('invoice-payments.show', [
                'publicToken' => $invoice->public_token,
            ])
            ->with(
                'success',
                '決済結果を確認しています。入金状況が反映されるまで、少しお待ちください。'
            );
    }
}
