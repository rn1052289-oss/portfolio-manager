<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Webhook;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Models\Invoice;
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

        /*
         * 請求書に紐づかない既存の案件決済では、
         * Invoice同期処理を行わないことを確認する。
         */
        $this->assertNull($payment->invoice_id);
        $this->assertDatabaseCount('invoices', 0);

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        $this->assertNotNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
        $this->assertNull($payment->failure_message);
    }

    /**
     * 請求書に紐づくPaymentIntentが成功すると、
     * PaymentとInvoiceが同じ支払い完了日時で同期される。
     */
    public function test_succeeded_invoice_payment_marks_invoice_as_paid(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_invoice_payment_success_test',
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
        $invoice->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        $this->assertNotNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
        $this->assertNull($payment->failure_message);

        $this->assertSame(
            Invoice::STATUS_PAID,
            $invoice->status,
        );

        $this->assertNotNull($invoice->paid_at);

        /*
         * Invoiceの入金日時には、Paymentの入金日時が
         * そのまま使用されていることを確認する。
         */
        $this->assertSame(
            $payment->paid_at?->toIso8601String(),
            $invoice->paid_at?->toIso8601String(),
        );
    }

    /**
     * 請求書決済の成功Webhookが重複しても、
     * PaymentとInvoiceの支払い完了日時は上書きされない。
     */
    public function test_duplicate_succeeded_invoice_payment_preserves_paid_at(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        /*
         * 同一イベントとして扱えるよう、
         * 同じ本文と署名を2回送信する。
         */
        $payload = $this->stripeEventPayload(
            eventId: 'evt_duplicate_invoice_payment_test',
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
        $invoice->refresh();

        $firstPaymentPaidAt = $payment->paid_at?->toIso8601String();
        $firstInvoicePaidAt = $invoice->paid_at?->toIso8601String();

        $this->assertNotNull($firstPaymentPaidAt);
        $this->assertNotNull($firstInvoicePaidAt);

        $this->assertSame(
            $firstPaymentPaidAt,
            $firstInvoicePaidAt,
        );

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
        $invoice->refresh();

        $this->assertSame(
            Payment::STATUS_SUCCEEDED,
            $payment->status,
        );

        $this->assertSame(
            Invoice::STATUS_PAID,
            $invoice->status,
        );

        /*
         * 2回目のWebhookで支払い完了日時が
         * 新しい時刻へ上書きされていないことを確認する。
         */
        $this->assertSame(
            $firstPaymentPaidAt,
            $payment->paid_at?->toIso8601String(),
        );

        $this->assertSame(
            $firstInvoicePaidAt,
            $invoice->paid_at?->toIso8601String(),
        );

        $this->assertSame(
            $payment->paid_at?->toIso8601String(),
            $invoice->paid_at?->toIso8601String(),
        );

        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * 請求書決済が処理中の場合、
     * Paymentはprocessingへ同期されるがInvoiceはpaidにならない。
     */
    public function test_processing_invoice_payment_does_not_mark_invoice_as_paid(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_CONFIRMATION,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_PROCESSING,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_invoice_payment_processing_test',
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
        $invoice->refresh();

        $this->assertSame(
            Payment::STATUS_PROCESSING,
            $payment->status,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);
        $this->assertNull($payment->failure_message);

        /*
         * processingは入金確定ではないため、
         * Invoiceは送信済みのまま維持する。
         */
        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status,
        );

        $this->assertNull($invoice->paid_at);
    }

    /**
     * 請求書決済がキャンセルされた場合、
     * Paymentはcanceledへ同期されるがInvoiceはpaidにならない。
     */
    public function test_canceled_invoice_payment_does_not_mark_invoice_as_paid(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_PROCESSING,
        );

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_CANCELED,
        );

        $response = $this->postSignedWebhook(
            eventId: 'evt_invoice_payment_canceled_test',
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
        $invoice->refresh();

        $this->assertSame(
            Payment::STATUS_CANCELED,
            $payment->status,
        );

        $this->assertNotNull($payment->canceled_at);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->failure_message);

        /*
         * canceledは入金成功ではないため、
         * Invoiceは送信済みのまま維持する。
         */
        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status,
        );

        $this->assertNull($invoice->paid_at);
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
     * Webhookテスト用のInvoiceとPaymentを作成する。
     *
     * @return array{0: Invoice, 1: Payment}
     */
    private function createInvoicePayment(
        string $invoiceStatus,
        string $paymentStatus,
    ): array {
        $invoice = Invoice::factory()
            ->sent()
            ->create([
                'status' => $invoiceStatus,
                'total_amount' => 100000,
                'currency' => 'jpy',
                'paid_at' => null,
                'canceled_at' => null,
            ]);

        /*
         * invoiceリレーションからPaymentインスタンスを作成する。
         * この時点でinvoice_idは自動的に設定される。
         */
        $payment = $invoice->payments()->make([
            'project_id' => $invoice->project_id,
            'stripe_payment_intent_id' => 'pi_invoice_webhook_'.Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'status' => $paymentStatus,
            'description' => '請求書Webhook Featureテスト用決済',
            'failure_message' => null,
            'paid_at' => null,
            'canceled_at' => null,
        ]);

        /*
         * user_idはPaymentモデルのマスアサインメント設定に
         * 影響されないよう、直接代入する。
         */
        $payment->user_id = $invoice->user_id;
        $payment->save();

        return [$invoice, $payment];
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

    /**
     * PaymentとInvoiceの金額が一致しない場合、
     * 請求書への入金反映を拒否してトランザクションをロールバックする。
     */
    public function test_invoice_payment_amount_mismatch_is_rejected(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        /*
         * Payment作成後にInvoiceの金額だけを変更し、
         * データ不整合が発生した状態を再現する。
         */
        $invoice->forceFill([
            'total_amount' => $payment->amount + 1000,
        ])->save();

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        /*
         * LogicExceptionをLaravelの例外ハンドラーで500へ変換せず、
         * Serviceが異常を検出したことを直接確認する。
         */
        $this->withoutExceptionHandling();

        try {
            $this->postSignedWebhook(
                eventId: 'evt_invoice_payment_amount_mismatch_test',
                eventType: 'payment_intent.succeeded',
                objectId: $payment->stripe_payment_intent_id,
            );

            $this->fail(
                'PaymentとInvoiceの金額不一致で例外が発生しませんでした。'
            );
        } catch (\LogicException $exception) {
            $this->assertSame(
                'PaymentとInvoiceの金額が一致しません。',
                $exception->getMessage(),
            );
        }

        $payment->refresh();
        $invoice->refresh();

        /*
         * Invoice同期で例外が発生したため、
         * 先に行われたPayment同期もロールバックされる。
         */
        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status,
        );

        $this->assertNull($invoice->paid_at);
    }

    /**
     * PaymentとInvoiceの通貨が一致しない場合、
     * 請求書への入金反映を拒否してトランザクションをロールバックする。
     */
    public function test_invoice_payment_currency_mismatch_is_rejected(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        /*
         * Paymentはjpyのまま、Invoiceだけusdへ変更し、
         * 通貨の不整合が発生した状態を再現する。
         */
        $invoice->forceFill([
            'currency' => 'usd',
        ])->save();

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        /*
         * LogicExceptionを直接確認するため、
         * Laravelの例外ハンドラーを無効にする。
         */
        $this->withoutExceptionHandling();

        try {
            $this->postSignedWebhook(
                eventId: 'evt_invoice_payment_currency_mismatch_test',
                eventType: 'payment_intent.succeeded',
                objectId: $payment->stripe_payment_intent_id,
            );

            $this->fail(
                'PaymentとInvoiceの通貨不一致で例外が発生しませんでした。'
            );
        } catch (\LogicException $exception) {
            $this->assertSame(
                'PaymentとInvoiceの通貨が一致しません。',
                $exception->getMessage(),
            );
        }

        $payment->refresh();
        $invoice->refresh();

        /*
         * Invoice同期時の例外によって、
         * Paymentのsucceeded更新も取り消される。
         */
        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status,
        );

        $this->assertSame(
            'jpy',
            $payment->currency,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status,
        );

        $this->assertSame(
            'usd',
            $invoice->currency,
        );

        $this->assertNull($invoice->paid_at);
    }

    /**
     * draftのInvoiceへ成功決済が紐づいた場合、
     * 異常状態として検出し、入金反映を行わない。
     */
    public function test_succeeded_payment_for_draft_invoice_is_rejected(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        /*
         * 通常の公開決済フローではdraftから決済を開始できない。
         * ここではDBの不整合や実装ミスを想定し、
         * Payment作成後にInvoiceをdraftへ戻す。
         */
        $invoice->forceFill([
            'status' => Invoice::STATUS_DRAFT,
            'paid_at' => null,
        ])->save();

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        $this->withoutExceptionHandling();

        try {
            $this->postSignedWebhook(
                eventId: 'evt_draft_invoice_payment_success_test',
                eventType: 'payment_intent.succeeded',
                objectId: $payment->stripe_payment_intent_id,
            );

            $this->fail(
                'draftのInvoiceへの成功反映で例外が発生しませんでした。'
            );
        } catch (\LogicException $exception) {
            $this->assertSame(
                '支払い成功を反映できない請求書ステータスです。',
                $exception->getMessage(),
            );
        }

        $payment->refresh();
        $invoice->refresh();

        /*
         * Invoice同期時の例外によって、
         * 先に実行されたPayment同期もロールバックされる。
         */
        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        $this->assertSame(
            Invoice::STATUS_DRAFT,
            $invoice->status,
        );

        $this->assertNull($invoice->paid_at);
    }

    /**
     * canceledのInvoiceへ成功決済が紐づいた場合、
     * 異常状態として検出し、入金反映を行わない。
     */
    public function test_succeeded_payment_for_canceled_invoice_is_rejected(): void
    {
        [$invoice, $payment] = $this->createInvoicePayment(
            invoiceStatus: Invoice::STATUS_SENT,
            paymentStatus: Payment::STATUS_REQUIRES_PAYMENT_METHOD,
        );

        /*
         * Payment作成後に事業者が請求書を取り消した状況や、
         * データ不整合が発生した状態を再現する。
         */
        $invoice->forceFill([
            'status' => Invoice::STATUS_CANCELED,
            'paid_at' => null,
            'canceled_at' => now(),
        ])->save();

        $originalCanceledAt = $invoice->canceled_at?->toIso8601String();

        $this->assertNotNull($originalCanceledAt);

        $this->bindFakePaymentIntent(
            payment: $payment,
            status: Payment::STATUS_SUCCEEDED,
        );

        /*
         * Serviceが投げるLogicExceptionを直接確認する。
         */
        $this->withoutExceptionHandling();

        try {
            $this->postSignedWebhook(
                eventId: 'evt_canceled_invoice_payment_success_test',
                eventType: 'payment_intent.succeeded',
                objectId: $payment->stripe_payment_intent_id,
            );

            $this->fail(
                'canceledのInvoiceへの成功反映で例外が発生しませんでした。'
            );
        } catch (\LogicException $exception) {
            $this->assertSame(
                '支払い成功を反映できない請求書ステータスです。',
                $exception->getMessage(),
            );
        }

        $payment->refresh();
        $invoice->refresh();

        /*
         * Invoice同期時に例外が発生したため、
         * Paymentのsucceeded更新もロールバックされる。
         */
        $this->assertSame(
            Payment::STATUS_REQUIRES_PAYMENT_METHOD,
            $payment->status,
        );

        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->canceled_at);

        /*
         * 取り消し済みのInvoiceは変更せず維持する。
         */
        $this->assertSame(
            Invoice::STATUS_CANCELED,
            $invoice->status,
        );

        $this->assertNull($invoice->paid_at);

        $this->assertSame(
            $originalCanceledAt,
            $invoice->canceled_at?->toIso8601String(),
        );
    }
}
