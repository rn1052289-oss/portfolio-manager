<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Webhook;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Stripe\PaymentIntent;
use Tests\Fakes\Stripe\FakeStripeService;
use Tests\TestCase;

final class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_feature_test_secret';

    /**
     * 各テストで使用するWebhookシークレットを設定する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    /**
     * 正しい署名のpayment_intent.succeededを受信すると、
     * LaravelのPaymentが成功状態へ同期される。
     */
    public function test_succeeded_event_synchronizes_payment(): void
    {
        $payment = $this->createPayment(
            status: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_webhook_success_test',
            eventType: 'payment_intent.succeeded',
            objectId: $payment->stripe_payment_intent_id,
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        $this->assertNotNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
        $this->assertNull($payment->failure_message);
    }

    /**
     * 不正なStripe署名は400で拒否され、
     * Paymentの同期処理へ進まない。
     */
    public function test_invalid_signature_returns_bad_request(): void
    {
        /*
         * Controllerの依存解決時に本物のStripeServiceが
         * 生成されないようFakeへ差し替える。
         *
         * PaymentIntentを設定していないため、
         * Stripe処理まで進んだ場合はテストが失敗する。
         */
        $this->app->instance(
            StripeServiceInterface::class,
            new FakeStripeService,
        );

        $payload = $this->stripeEventPayload(
            eventId: 'evt_invalid_signature_test',
            eventType: 'payment_intent.succeeded',
            objectId: 'pi_invalid_signature_test',
        );

        $invalidSignature = sprintf(
            't=%d,v1=invalid_signature',
            time(),
        );

        $response = $this->postRawWebhook(
            payload: $payload,
            signature: $invalidSignature,
        );

        $response
            ->assertStatus(400)
            ->assertJsonPath(
                'message',
                'Webhookリクエストを検証できませんでした。',
            );

        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * payment_intent.payment_failedを受信すると、
     * 最新の失敗状態と失敗理由が同期される。
     */
    public function test_payment_failed_event_synchronizes_failure_details(): void
    {
        $payment = $this->createPayment(
            status: Payment::STATUS_PROCESSING,
        );

        $failureMessage = 'カードが拒否されました。';

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            failureMessage: $failureMessage,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_webhook_failed_test',
            eventType: 'payment_intent.payment_failed',
            objectId: $payment->stripe_payment_intent_id,
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status,
        );

        $this->assertSame(
            $failureMessage,
            $payment->failure_message,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
    }

    /**
     * Laravel側に存在しないPaymentIntentのイベントでも、
     * エラーやStripe API通信を発生させず200を返す。
     */
    public function test_unknown_payment_intent_is_safely_ignored(): void
    {
        /*
         * PaymentIntentを設定していないFakeを使用する。
         * Stripe API再取得まで進んだ場合は例外になり、
         * テストが失敗する。
         */
        $this->app->instance(
            StripeServiceInterface::class,
            new FakeStripeService,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_unknown_payment_intent_test',
            eventType: 'payment_intent.succeeded',
            objectId: 'pi_not_registered_in_laravel',
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'イベントを受信しました。',
            );

        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * 同一のWebhookイベントを複数回受信しても、
     * Paymentやpaid_atが壊れない。
     */
    public function test_duplicate_event_is_processed_safely(): void
    {
        $payment = $this->createPayment(
            status: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        /*
         * 同じイベント本文と同じ署名を2回送信する。
         */
        $payload = $this->stripeEventPayload(
            eventId: 'evt_duplicate_webhook_test',
            eventType: 'payment_intent.succeeded',
            objectId: $payment->stripe_payment_intent_id,
        );

        $signature = $this->signatureHeader(
            payload: $payload,
            secret: self::WEBHOOK_SECRET,
        );

        $firstResponse = $this->postRawWebhook(
            payload: $payload,
            signature: $signature,
        );

        $firstResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $firstPaidAt = $payment->paid_at?->toIso8601String();

        $this->assertNotNull($firstPaidAt);

        $secondResponse = $this->postRawWebhook(
            payload: $payload,
            signature: $signature,
        );

        $secondResponse
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        /*
         * 再送によって初回の支払い完了日時が
         * 上書きされていないことを確認する。
         */
        $this->assertSame(
            $firstPaidAt,
            $payment->paid_at?->toIso8601String(),
        );

        $this->assertNull($payment->canceled_at);
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * payment_intent.processingを受信すると、
     * Paymentが処理中状態へ同期される。
     */
    public function test_processing_event_synchronizes_payment(): void
    {
        $payment = $this->createPayment(
            status: Payment::STATUS_REQUIRES_CONFIRMATION,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_PROCESSING,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_webhook_processing_test',
            eventType: 'payment_intent.processing',
            objectId: $payment->stripe_payment_intent_id,
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_PROCESSING,
            $payment->status,
        );

        $this->assertNull($payment->failure_message);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
    }

    /**
     * payment_intent.canceledを受信すると、
     * Paymentがキャンセル状態へ同期される。
     */
    public function test_canceled_event_synchronizes_payment(): void
    {
        $payment = $this->createPayment(
            status: Payment::STATUS_PROCESSING,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_CANCELED,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_webhook_canceled_test',
            eventType: 'payment_intent.canceled',
            objectId: $payment->stripe_payment_intent_id,
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                '決済状態を同期しました。',
            );

        $payment->refresh();

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $payment->status,
        );

        $this->assertNotNull($payment->canceled_at);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->failure_message);
    }

    /**
     * 正しい署名でも処理対象外イベントなら、
     * Payment処理を実行せず200を返す。
     */
    public function test_unsupported_event_is_safely_ignored(): void
    {
        /*
         * Stripe処理まで進んでいないことを確認するため、
         * PaymentIntentを設定していないFakeを使用する。
         */
        $this->app->instance(
            StripeServiceInterface::class,
            new FakeStripeService,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_unsupported_event_test',
            eventType: 'customer.created',
            objectId: 'cus_unsupported_event_test',
            objectType: 'customer',
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'イベントを受信しました。',
            );

        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * Webhookテスト用のPaymentを作成する。
     */
    private function createPayment(string $status): Payment
    {
        $user = User::factory()->create();

        return $user->payments()->create([
            'project_id' => null,
            'stripe_payment_intent_id' => 'pi_webhook_'.Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => 100000,
            'currency' => 'jpy',
            'status' => $status,
            'description' => 'Webhook Featureテスト用決済',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => null,
        ]);
    }

    /**
     * Stripe APIから再取得されるPaymentIntentをFakeへ設定する。
     */
    private function bindFakePaymentIntent(
        Payment $payment,
        string $status,
        ?string $failureMessage = null,
    ): void {
        $fakePaymentIntent = PaymentIntent::constructFrom([
            'id' => $payment->stripe_payment_intent_id,
            'object' => 'payment_intent',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $status,
            'description' => $payment->description,
            'last_payment_error' => $failureMessage === null
                ? null
                : [
                    'message' => $failureMessage,
                ],
        ]);

        $fakeStripeService = (new FakeStripeService)
            ->returnPaymentIntent($fakePaymentIntent);

        $this->app->instance(
            StripeServiceInterface::class,
            $fakeStripeService,
        );
    }

    /**
     * 正しい署名付きWebhookを送信する。
     */
    private function postSignedWebhook(
        string $eventId,
        string $eventType,
        string $objectId,
        string $objectType = 'payment_intent',
    ): TestResponse {
        $payload = $this->stripeEventPayload(
            eventId: $eventId,
            eventType: $eventType,
            objectId: $objectId,
            objectType: $objectType,
        );

        $signature = $this->signatureHeader(
            payload: $payload,
            secret: self::WEBHOOK_SECRET,
        );

        return $this->postRawWebhook(
            payload: $payload,
            signature: $signature,
        );
    }

    /**
     * 生のJSON本文と署名ヘッダーを使ってWebhookを送信する。
     */
    private function postRawWebhook(
        string $payload,
        string $signature,
    ): TestResponse {
        return $this->call(
            method: 'POST',
            uri: route('api.webhooks.stripe'),
            parameters: [],
            cookies: [],
            files: [],
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $signature,
            ],
            content: $payload,
        );
    }

    /**
     * Stripe Webhookに近いイベントJSONを作成する。
     */
    private function stripeEventPayload(
        string $eventId,
        string $eventType,
        string $objectId,
        string $objectType = 'payment_intent',
    ): string {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => $eventType,
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'data' => [
                'object' => [
                    'id' => $objectId,
                    'object' => $objectType,
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Stripeと同じ形式の署名ヘッダーを作成する。
     */
    private function signatureHeader(
        string $payload,
        string $secret,
    ): string {
        $timestamp = time();

        $signature = hash_hmac(
            'sha256',
            "{$timestamp}.{$payload}",
            $secret,
        );

        return sprintf(
            't=%d,v1=%s',
            $timestamp,
            $signature,
        );
    }
}
