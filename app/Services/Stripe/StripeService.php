<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use RuntimeException;
use Stripe\Balance;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

final class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException(
                'Stripeのシークレットキーが設定されていません。'
            );
        }

        $this->client = new StripeClient($secret);
    }

    /**
     * Stripeアカウントの残高情報を取得する。
     */
    public function retrieveBalance(): Balance
    {
        return $this->client->balance->retrieve([]);
    }

    /**
     * StripeにPaymentIntentを作成する。
     *
     * @param  array<string, string>  $metadata
     */
    public function createPaymentIntent(
        int $amount,
        string $currency,
        ?string $description,
        array $metadata,
        string $idempotencyKey,
    ): PaymentIntent {
        $parameters = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => $metadata,
        ];

        if (is_string($description) && $description !== '') {
            $parameters['description'] = $description;
        }

        return $this->client->paymentIntents->create(
            $parameters,
            [
                'idempotency_key' => $idempotencyKey,
            ],
        );
    }
}
