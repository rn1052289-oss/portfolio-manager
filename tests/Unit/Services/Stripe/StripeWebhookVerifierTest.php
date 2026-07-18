<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Stripe;

use App\Services\Stripe\StripeWebhookVerifier;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Tests\TestCase;

final class StripeWebhookVerifierTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_webhook_secret';

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
     * 正しい署名なら、検証済みのStripeイベントを取得できる。
     */
    public function test_valid_signature_returns_verified_stripe_event(): void
    {
        $payload = $this->paymentIntentSucceededPayload();

        $signature = $this->signatureHeader(
            payload: $payload,
            secret: self::WEBHOOK_SECRET,
        );

        $event = app(StripeWebhookVerifier::class)->verify(
            payload: $payload,
            signature: $signature,
        );

        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame(
            'evt_test_payment_intent_succeeded',
            $event->id,
        );
        $this->assertSame(
            'payment_intent.succeeded',
            $event->type,
        );
        $this->assertSame(
            'pi_test_webhook',
            $event->data->object->id,
        );
    }

    /**
     * 不正な署名なら、署名検証例外が発生する。
     */
    public function test_invalid_signature_throws_exception(): void
    {
        $payload = $this->paymentIntentSucceededPayload();

        $invalidSignature = sprintf(
            't=%d,v1=invalid_signature',
            time(),
        );

        $this->expectException(
            SignatureVerificationException::class
        );

        app(StripeWebhookVerifier::class)->verify(
            payload: $payload,
            signature: $invalidSignature,
        );
    }

    /**
     * テスト用のPaymentIntent成功イベントをJSONで作成する。
     */
    private function paymentIntentSucceededPayload(): string
    {
        return json_encode([
            'id' => 'evt_test_payment_intent_succeeded',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_webhook',
                    'object' => 'payment_intent',
                    'amount' => 100000,
                    'currency' => 'jpy',
                    'status' => 'succeeded',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Stripeと同じ形式の署名ヘッダーを生成する。
     */
    private function signatureHeader(
        string $payload,
        string $secret,
    ): string {
        $timestamp = time();

        $signedPayload = "{$timestamp}.{$payload}";

        $signature = hash_hmac(
            'sha256',
            $signedPayload,
            $secret,
        );

        return sprintf(
            't=%d,v1=%s',
            $timestamp,
            $signature,
        );
    }
}