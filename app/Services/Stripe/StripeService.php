<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use RuntimeException;
use Stripe\Balance;
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
}
