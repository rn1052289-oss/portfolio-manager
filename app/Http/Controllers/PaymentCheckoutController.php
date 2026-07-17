<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payment\CreatePaymentIntentAction;
use App\Contracts\Stripe\StripeServiceInterface;
use App\Http\Requests\Web\Payment\StoreCheckoutPaymentRequest;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\IdempotencyException;

final class PaymentCheckoutController extends Controller
{
    /**
     * 案件の決済内容確認画面を表示する。
     */
    public function create(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load('customer');

        /*
         * 支払金額はブラウザから受け取らず、
         * Laravel側のprojects.budgetから決定する。
         */
        $amount = $project->budget !== null
            ? (int) $project->budget
            : null;

        $isPayable = $amount !== null
            && $amount >= 50
            && $amount <= 99999999;

        $customerName = $project->customer?->name
            ?? $project->client_name
            ?? '未設定';

        $paymentDescription = $this->paymentDescription($project);

        /*
         * 同じフォームの再送信でPaymentIntentが
         * 重複作成されないようにする識別キー。
         */
        $idempotencyKey = (string) Str::uuid();

        return view('payments.create', compact(
            'project',
            'amount',
            'isPayable',
            'customerName',
            'paymentDescription',
            'idempotencyKey',
        ));
    }

    /**
     * ブラウザ決済用のPaymentIntentを作成する。
     */
    public function store(
        StoreCheckoutPaymentRequest $request,
        Project $project,
        CreatePaymentIntentAction $createPaymentIntentAction,
    ): RedirectResponse {
        /*
         * GET画面だけでなく、実際に作成するPOSTでも
         * 案件の所有権を確認する。
         */
        $this->authorize('view', $project);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        /*
         * フォームからamountを受け取らず、
         * DBに保存されている案件金額を使用する。
         */
        $amount = $project->budget !== null
            ? (int) $project->budget
            : null;

        if (
            $amount === null
            || $amount < 50
            || $amount > 99999999
        ) {
            return back()
                ->withErrors([
                    'payment' => '見積金額が未設定、50円未満、または決済上限を超えています。',
                ])
                ->withInput();
        }

        $validated = $request->validated();

        try {
            $result = $createPaymentIntentAction->execute(
                user: $user,
                project: $project,
                amount: $amount,
                description: $this->paymentDescription($project),
                idempotencyKey: (string) $validated['idempotency_key'],
            );
        } catch (IdempotencyException $exception) {
            Log::notice('ブラウザ決済の冪等性キーが競合しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
                'stripe_code' => $exception->getStripeCode(),
                'stripe_request_id' => $exception->getRequestId(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return back()
                ->withErrors([
                    'payment' => '決済処理が重複しました。画面を再読み込みして、もう一度お試しください。',
                ])
                ->withInput();
        } catch (ApiErrorException $exception) {
            Log::error('ブラウザ決済のPaymentIntent作成に失敗しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
                'stripe_code' => $exception->getStripeCode(),
                'stripe_request_id' => $exception->getRequestId(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return back()
                ->withErrors([
                    'payment' => 'Stripeとの通信に失敗しました。時間を置いて再度お試しください。',
                ])
                ->withInput();
        } catch (InvalidArgumentException $exception) {
            Log::warning('ブラウザ決済の金額検証に失敗しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
            ]);

            return back()
                ->withErrors([
                    'payment' => '決済金額が正しくありません。案件の見積金額を確認してください。',
                ])
                ->withInput();
        } catch (LogicException $exception) {
            Log::warning('ブラウザ決済の所有権検証に失敗しました。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
            ]);

            abort(403);
        } catch (RuntimeException $exception) {
            Log::error('ブラウザ決済のStripe設定が正しくありません。', [
                'user_id' => $user->getKey(),
                'project_id' => $project->getKey(),
                'exception' => $exception::class,
            ]);

            return back()
                ->withErrors([
                    'payment' => '現在、決済機能を利用できません。時間を置いて再度お試しください。',
                ])
                ->withInput();
        }

        $payment = $result['payment'];

        /*
         * client_secretをURLへ含めず、
         * Laravel側のPayment IDだけで決済入力画面へ移動する。
         */
        return redirect()
            ->route('payments.checkout', $payment)
            ->with('success', '決済準備を作成しました。');
    }

    /**
     * Stripe Payment Elementを配置する決済入力画面を表示する。
     */
    public function checkout(
        Payment $payment,
        StripeServiceInterface $stripeService,
    ): View|RedirectResponse {
        /*
         * Route Model BindingでPaymentを取得したあと、
         * PaymentPolicyで所有者本人か確認する。
         */
        $this->authorize('view', $payment);

        $payment->load('project.customer');

        $project = $payment->project;

        if (! $project instanceof Project) {
            return redirect()
                ->route('projects.index')
                ->withErrors([
                    'payment' => 'この決済に紐づく案件が見つかりません。',
                ]);
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(401);
        }

        /*
         * Paymentだけでなく、関連するProjectも
         * ログインユーザーの所有物か再確認する。
         */
        if ((int) $project->user_id !== (int) $user->getKey()) {
            abort(403);
        }

        try {
            /*
             * Laravelに保存してあるStripe IDを使い、
             * Stripe APIから作成済みPaymentIntentを再取得する。
             */
            $paymentIntent = $stripeService->retrievePaymentIntent(
                $payment->stripe_payment_intent_id,
            );
        } catch (ApiErrorException $exception) {
            Log::error('決済入力画面用のPaymentIntent取得に失敗しました。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'exception' => $exception::class,
                'stripe_code' => $exception->getStripeCode(),
                'stripe_request_id' => $exception->getRequestId(),
                'http_status' => $exception->getHttpStatus(),
            ]);

            return redirect()
                ->route('projects.payments.create', $project)
                ->withErrors([
                    'payment' => '決済情報を取得できませんでした。時間を置いて再度お試しください。',
                ]);
        }

        /*
         * Laravel側PaymentとStripe側PaymentIntentが
         * 同じ決済を表しているか確認する。
         */
        if (
            $paymentIntent->id !== $payment->stripe_payment_intent_id
            || (int) $paymentIntent->amount !== (int) $payment->amount
            || strtolower((string) $paymentIntent->currency)
            !== strtolower((string) $payment->currency)
        ) {
            Log::critical('LaravelとStripeの決済情報が一致しません。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return redirect()
                ->route('projects.payments.create', $project)
                ->withErrors([
                    'payment' => '決済情報の整合性を確認できませんでした。新しい決済を作成してください。',
                ]);
        }

        $clientSecret = $paymentIntent->client_secret;

        /*
         * client_secret自体はログへ出さない。
         */
        if (! is_string($clientSecret) || $clientSecret === '') {
            Log::error('PaymentIntentにclient_secretがありません。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return redirect()
                ->route('projects.payments.create', $project)
                ->withErrors([
                    'payment' => '決済画面を準備できませんでした。新しい決済を作成してください。',
                ]);
        }

        /*
         * 公開鍵はStripe.jsで使用するため、ブラウザへ渡してよい。
         * Stripe秘密鍵は絶対にここへ渡さない。
         */
        $stripePublicKey = config('services.stripe.key');

        if (
            ! is_string($stripePublicKey)
            || $stripePublicKey === ''
            || ! str_starts_with($stripePublicKey, 'pk_')
        ) {
            Log::error('Stripe公開鍵が正しく設定されていません。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
            ]);

            return redirect()
                ->route('projects.payments.create', $project)
                ->withErrors([
                    'payment' => '決済画面を初期化できませんでした。Stripe設定を確認してください。',
                ]);
        }

        /*
         * local・testing環境では、テスト用公開鍵だけを許可する。
         */
        if (
            app()->environment(['local', 'testing'])
            && ! str_starts_with($stripePublicKey, 'pk_test_')
        ) {
            Log::critical('ローカル環境でStripe本番公開鍵が検出されました。', [
                'user_id' => $user->getKey(),
                'payment_id' => $payment->getKey(),
                'environment' => app()->environment(),
            ]);

            return redirect()
                ->route('projects.payments.create', $project)
                ->withErrors([
                    'payment' => 'ローカル環境ではStripeテスト公開鍵を使用してください。',
                ]);
        }

        $stripeStatus = (string) $paymentIntent->status;

        $canContinue = in_array(
            $stripeStatus,
            [
                Payment::STATUS_REQUIRES_PAYMENT_METHOD,
                Payment::STATUS_REQUIRES_CONFIRMATION,
            ],
            true,
        );

        $statusLabels = [
            Payment::STATUS_REQUIRES_PAYMENT_METHOD => '支払い方法の入力待ち',
            Payment::STATUS_REQUIRES_CONFIRMATION => '決済確定待ち',
            Payment::STATUS_REQUIRES_ACTION => '追加認証待ち',
            Payment::STATUS_PROCESSING => '決済処理中',
            Payment::STATUS_SUCCEEDED => '支払い完了',
            Payment::STATUS_CANCELED => 'キャンセル済み',
        ];

        $statusLabel = $statusLabels[$stripeStatus]
            ?? $stripeStatus;

        $customerName = $project->customer?->name
            ?? $project->client_name
            ?? '未設定';

        return view('payments.checkout', compact(
            'payment',
            'project',
            'customerName',
            'clientSecret',
            'stripePublicKey',
            'stripeStatus',
            'statusLabel',
            'canContinue',
        ));
    }

    /**
     * Stripeとpaymentsテーブルへ保存する決済説明文を作成する。
     */
    private function paymentDescription(Project $project): string
    {
        return sprintf(
            '案件「%s」の制作費',
            $project->title,
        );
    }
}
