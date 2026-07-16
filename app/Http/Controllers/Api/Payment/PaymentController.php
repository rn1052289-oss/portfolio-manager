<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\Stripe\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\IdempotencyException;

final class PaymentController extends Controller
{
    /**
     * StripeにPaymentIntentを作成し、
     * Laravelのpaymentsテーブルへ保存する。
     */
    public function store(
        StorePaymentRequest $request,
        StripeService $stripeService,
    ): JsonResponse {
        $validated = $request->validated();
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => '認証が必要です。',
            ], 401);
        }

        /*
         * FormRequestでも所有権を確認しているが、
         * 実際に取得するときもログインユーザーで絞り込む。
         */
        $project = Project::query()
            ->whereKey((int) $validated['project_id'])
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        $currency = config('services.stripe.currency', 'jpy');

        if (! is_string($currency) || strlen($currency) !== 3) {
            Log::error('Stripeの通貨設定が正しくありません。', [
                'currency' => $currency,
            ]);

            return response()->json([
                'message' => '決済設定に問題があります。',
            ], 500);
        }

        $amount = (int) $validated['amount'];
        $idempotencyKey = (string) $validated['idempotency_key'];

        $description = isset($validated['description'])
            ? trim((string) $validated['description'])
            : null;

        if ($description === '') {
            $description = null;
        }

        /*
         * Stripe DashboardとLaravelのデータを対応させる情報。
         * 機密情報はmetadataへ入れない。
         */
        $metadata = [
            'user_id' => (string) $user->getKey(),
            'project_id' => (string) $project->getKey(),
            'project_title' => (string) $project->title,
        ];

        try {
            $paymentIntent = $stripeService->createPaymentIntent(
                amount: $amount,
                currency: $currency,
                description: $description,
                metadata: $metadata,
                idempotencyKey: $idempotencyKey,
            );
        } catch (IdempotencyException $exception) {
            Log::notice('Stripeの冪等性キーが競合しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => '同じIdempotency-Keyが別の決済内容で使用されています。',
                'errors' => [
                    'idempotency_key' => [
                        '新しいUUIDを指定して、もう一度実行してください。',
                    ],
                ],
            ], 409);
        } catch (ApiErrorException $exception) {
            Log::error('StripeのPaymentIntent作成に失敗しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Stripeとの通信に失敗しました。時間を置いて再度お試しください。',
            ], 502);
        }

        $failureMessage = data_get(
            $paymentIntent,
            'last_payment_error.message'
        );

        /*
         * DB保存に失敗しても、同じIdempotency-Keyで再実行すれば
         * Stripe側のPaymentIntentを重複作成せずに再取得できる。
         */
        $payment = DB::transaction(function () use ($user, $project, $paymentIntent, $idempotencyKey, $description, $metadata, $failureMessage): Payment {
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
                    'description' => $description,
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

        return response()->json([
            'message' => $payment->wasRecentlyCreated
                ? '決済準備を作成しました。'
                : '既存の決済準備を取得しました。',
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'project_id' => $payment->project_id,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'description' => $payment->description,
                ],
                'project' => [
                    'id' => $project->id,
                    'title' => $project->title,
                ],
                'client_secret' => $paymentIntent->client_secret,
            ],
        ], $payment->wasRecentlyCreated ? 201 : 200);
    }
}
