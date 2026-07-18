<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use InvalidArgumentException;
use RuntimeException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Stripe\Exception\UnexpectedValueException;

final class StripeWebhookVerifier
{
    /**
     * Stripe Webhookの署名を検証し、
     * 検証済みのStripeイベントを返す。
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function verify(
        string $payload,
        ?string $signature,
    ): Event {
        $webhookSecret = config(
            'services.stripe.webhook_secret'
        );

        /*
         * Webhookシークレットが未設定の状態で、
         * 署名検証を実行しない。
         */
        if (
            !is_string($webhookSecret)
            || trim($webhookSecret) === ''
        ) {
            throw new RuntimeException(
                'StripeのWebhookシークレットが設定されていません。'
            );
        }

        /*
         * 空のリクエスト本文はWebhookとして処理しない。
         */
        if ($payload === '') {
            throw new InvalidArgumentException(
                'Stripe Webhookのペイロードが空です。'
            );
        }

        /*
         * Stripe-Signatureヘッダーがなければ、
         * 正規のStripe Webhookとして検証できない。
         */
        if (
            !is_string($signature)
            || trim($signature) === ''
        ) {
            throw new InvalidArgumentException(
                'Stripe-Signatureヘッダーがありません。'
            );
        }

        /*
         * 生のリクエスト本文、Stripe-Signature、
         * Webhookシークレットを使って公式SDKで検証する。
         */
        return Webhook::constructEvent(
            $payload,
            $signature,
            $webhookSecret,
        );
    }
}