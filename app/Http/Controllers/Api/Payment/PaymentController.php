<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payment;

use App\Actions\Payment\CreatePaymentIntentAction;
use App\Contracts\Stripe\StripeServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payment\ConfirmPaymentRequest;
use App\Http\Requests\Api\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use App\Services\Stripe\PaymentIntentSynchronizer;
use App\Services\Stripe\StripeTestPaymentMethodGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
        CreatePaymentIntentAction $createPaymentIntentAction,
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

        $amount = (int) $validated['amount'];
        $idempotencyKey = (string) $validated['idempotency_key'];

        $description = isset($validated['description'])
            ? (string) $validated['description']
            : null;

        try {
            /*
             * Stripe通信とPayment保存は、
             * Web決済からも再利用できるActionへ委譲する。
             */
            $result = $createPaymentIntentAction->execute(
                user: $user,
                project: $project,
                amount: $amount,
                description: $description,
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
        } catch (RuntimeException $exception) {
            Log::error('Stripeの決済設定が正しくありません。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => '決済設定に問題があります。',
            ], 500);
        }

        $payment = $result['payment'];
        $paymentIntent = $result['payment_intent'];

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

    /**
     * 作成済みのStripe PaymentIntentを確定し、
     * Stripeの最新状態をLaravelへ同期する。
     */
    public function confirm(
        ConfirmPaymentRequest $request,
        Payment $payment,
        StripeServiceInterface $stripeService,
        StripeTestPaymentMethodGuard $paymentMethodGuard,
        PaymentIntentSynchronizer $paymentIntentSynchronizer,
    ): JsonResponse {
        /*
         * Sanctum abilityとは別に、
         * このPaymentを所有するユーザーか確認する。
         */
        $this->authorize('confirm', $payment);

        $validated = $request->validated();
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'message' => '認証が必要です。',
            ], 401);
        }

        /*
         * Route Model Binding後にDBの最新状態を再取得する。
         */
        $payment->refresh();

        /*
         * すでに成功している場合はStripeへ再送しない。
         * 同じ決済を再課金せず、既存結果を返す。
         */
        if ($payment->isSucceeded()) {
            return response()->json([
                'message' => 'この決済はすでに完了しています。',
                'data' => [
                    'payment' => $this->paymentData($payment),
                ],
            ]);
        }

        /*
         * processing・requires_action・canceledなど、
         * 今回のAPIで再確定できない状態を拒否する。
         */
        if (! $payment->canBeConfirmed()) {
            return response()->json([
                'message' => '現在の決済状態では確定処理を実行できません。',
                'errors' => [
                    'status' => [
                        "現在のステータスは{$payment->status}です。",
                    ],
                ],
            ], 409);
        }

        $paymentMethod = (string) $validated['payment_method'];
        $idempotencyKey = (string) $validated['idempotency_key'];

        /*
         * local/testing環境・テスト秘密鍵・許可済みPaymentMethodの
         * すべてを満たす場合だけテスト決済を実行する。
         */
        if (! $paymentMethodGuard->allows($paymentMethod)) {
            Log::warning('Stripeテスト決済が安全ガードにより拒否されました。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'environment' => app()->environment(),
            ]);

            return response()->json([
                'message' => 'この支払い方法は現在の実行環境では使用できません。',
                'errors' => [
                    'payment_method' => [
                        '許可されたStripeテスト環境と支払い方法を使用してください。',
                    ],
                ],
            ], 403);
        }

        try {
            $paymentIntent = $stripeService->confirmPaymentIntent(
                paymentIntentId: $payment->stripe_payment_intent_id,
                paymentMethod: $paymentMethod,
                idempotencyKey: $idempotencyKey,
            );
        } catch (IdempotencyException $exception) {
            /*
             * 秘密鍵・トークン・client_secret・カード情報・
             * Stripeの詳細メッセージはログへ保存しない。
             */
            Log::notice('Stripe決済確定の冪等性キーが競合しました。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'exception' => $exception::class,
                'stripe_code' => $exception->getStripeCode(),
                'stripe_request_id' => $exception->getRequestId(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return response()->json([
                'message' => '同じIdempotency-Keyが別の確定処理で使用されています。',
                'errors' => [
                    'idempotency_key' => [
                        '新しいUUIDを指定して、もう一度実行してください。',
                    ],
                ],
            ], 409);
        } catch (ApiErrorException $exception) {
            Log::error('Stripeの決済確定処理に失敗しました。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'exception' => $exception::class,
                'stripe_code' => $exception->getStripeCode(),
                'stripe_request_id' => $exception->getRequestId(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return response()->json([
                'message' => 'Stripeで決済を確定できませんでした。時間を置いて再度お試しください。',
            ], 502);
        }

        $payment = $paymentIntentSynchronizer->sync(
            payment: $payment,
            paymentIntent: $paymentIntent,
        );

        if ($payment->isSucceeded()) {
            return response()->json([
                'message' => '決済が完了しました。',
                'data' => [
                    'payment' => $this->paymentData($payment),
                ],
            ]);
        }

        /*
         * succeeded以外でもStripeへのリクエスト自体が
         * 正常に受け付けられた場合は、最新状態を返す。
         */
        return response()->json([
            'message' => '決済処理を受け付けました。現在の状態を確認してください。',
            'data' => [
                'payment' => $this->paymentData($payment),
            ],
        ], 202);
    }

    /**
     * 決済APIレスポンスへ含める公開可能な情報を整形する。
     *
     * @return array<string, mixed>
     */
    private function paymentData(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'project_id' => $payment->project_id,
            'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'description' => $payment->description,
            'failure_message' => $payment->failure_message,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'canceled_at' => $payment->canceled_at?->toIso8601String(),
        ];
    }
}
