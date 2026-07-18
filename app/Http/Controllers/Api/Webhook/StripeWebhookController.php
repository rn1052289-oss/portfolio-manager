<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhook;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Stripe\PaymentIntentSynchronizer;
use App\Services\Stripe\StripeWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;

final class StripeWebhookController extends Controller
{
    /**
     * ClientWork Managerで処理するStripeイベントです。
     *
     * @var array<int, string>
     */
    private const SUPPORTED_EVENT_TYPES = [
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'payment_intent.processing',
        'payment_intent.canceled',
    ];

    /**
     * Stripe Webhookを受信する。
     */
    public function __invoke(
        Request $request,
        StripeWebhookVerifier $webhookVerifier,
        StripeServiceInterface $stripeService,
        PaymentIntentSynchronizer $paymentIntentSynchronizer,
    ): JsonResponse {
        try {
            /*
             * 署名検証には、加工していない生のリクエスト本文と
             * Stripe-Signatureヘッダーを使用する。
             */
            $event = $webhookVerifier->verify(
                payload: $request->getContent(),
                signature: $request->header('Stripe-Signature'),
            );
        } catch (
            InvalidArgumentException
            |UnexpectedValueException
            |SignatureVerificationException $exception
        ) {
            /*
             * リクエスト本文・署名値・秘密情報はログへ保存しない。
             */
            Log::warning('Stripe Webhookの検証に失敗しました。', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Webhookリクエストを検証できませんでした。',
            ], 400);
        } catch (RuntimeException $exception) {
            /*
             * Webhookシークレット未設定など、
             * Laravel側の設定エラーとして扱う。
             */
            Log::error('Stripe Webhookの設定に問題があります。', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Webhookの受信設定に問題があります。',
            ], 500);
        }

        $eventId = (string) $event->id;
        $eventType = (string) $event->type;

        /*
         * 今回処理しない正規のStripeイベントには200を返す。
         * エラーにするとStripeから不要な再送が発生する。
         */
        if (
            ! in_array(
                $eventType,
                self::SUPPORTED_EVENT_TYPES,
                true
            )
        ) {
            Log::info('処理対象外のStripe Webhookを受信しました。', [
                'stripe_event_id' => $eventId,
                'event_type' => $eventType,
            ]);

            return response()->json([
                'message' => 'イベントを受信しました。',
            ]);
        }

        /*
         * 署名検証済みイベントからPaymentIntent IDだけを取り出す。
         * payload内のステータスなどは、そのままDBへ保存しない。
         */
        $paymentIntentId = data_get(
            $event,
            'data.object.id'
        );

        if (
            ! is_string($paymentIntentId)
            || trim($paymentIntentId) === ''
        ) {
            Log::warning(
                'Stripe WebhookにPaymentIntent IDが含まれていません。',
                [
                    'stripe_event_id' => $eventId,
                    'event_type' => $eventType,
                ]
            );

            /*
             * 正しい署名のイベントだが、自アプリでは処理できない。
             * 同じ内容を再送されても解決しないため200を返す。
             */
            return response()->json([
                'message' => 'イベントを受信しました。',
            ]);
        }

        /*
         * PaymentIntent IDはunique制約があるため、
         * 1件のPaymentだけが取得される。
         */
        $paymentExists = Payment::query()
            ->where(
                'stripe_payment_intent_id',
                $paymentIntentId
            )
            ->exists();

        if (! $paymentExists) {
            Log::notice(
                'Laravel側に存在しないPaymentIntentのWebhookを受信しました。',
                [
                    'stripe_event_id' => $eventId,
                    'event_type' => $eventType,
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]
            );

            /*
             * 自アプリで作成していないPaymentIntentは同期できない。
             * 再送を繰り返させないため200を返す。
             */
            return response()->json([
                'message' => 'イベントを受信しました。',
            ]);
        }

        try {
            /*
             * Webhookイベントの到着順が前後しても、
             * Stripeの現在の最新状態を同期できるように再取得する。
             */
            $paymentIntent = $stripeService->retrievePaymentIntent(
                $paymentIntentId
            );
        } catch (ApiErrorException $exception) {
            /*
             * Stripe APIの一時障害などは再送対象にするため、
             * 成功レスポンスを返さない。
             */
            Log::error(
                'Webhook処理中にStripe PaymentIntentを取得できませんでした。',
                [
                    'stripe_event_id' => $eventId,
                    'event_type' => $eventType,
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'exception' => $exception::class,
                    'stripe_code' => $exception->getStripeCode(),
                    'stripe_request_id' => $exception->getRequestId(),
                    'http_status' => $exception->getHttpStatus(),
                ]
            );

            return response()->json([
                'message' => 'Webhook処理を完了できませんでした。',
            ], 500);
        }

        /*
         * 同じWebhookがほぼ同時に届いても、
         * paid_atやcanceled_atを安全に維持できるように行ロックを使う。
         *
         * Stripe API通信はトランザクションの外で完了させ、
         * DBロックを長時間保持しない。
         */
        $payment = DB::transaction(
            function () use ($paymentIntentId, $paymentIntent, $paymentIntentSynchronizer): Payment {
                $lockedPayment = Payment::query()
                    ->where(
                        'stripe_payment_intent_id',
                        $paymentIntentId
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                return $paymentIntentSynchronizer->sync(
                    payment: $lockedPayment,
                    paymentIntent: $paymentIntent,
                );
            }
        );

        Log::info('Stripe Webhookから決済状態を同期しました。', [
            'stripe_event_id' => $eventId,
            'event_type' => $eventType,
            'stripe_payment_intent_id' => $paymentIntentId,
            'payment_id' => $payment->getKey(),
            'payment_status' => $payment->status,
        ]);

        return response()->json([
            'message' => '決済状態を同期しました。',
        ]);
    }
}
