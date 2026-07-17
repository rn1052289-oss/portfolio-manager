<?php

declare(strict_types=1);

namespace App\Contracts\Stripe;

use Stripe\Balance;
use Stripe\PaymentIntent;

interface StripeServiceInterface
{
    public function retrieveBalance(): Balance;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createPaymentIntent(
        int $amount,
        string $currency,
        ?string $description,
        array $metadata,
        string $idempotencyKey,
    ): PaymentIntent;

    public function confirmPaymentIntent(
        string $paymentIntentId,
        string $paymentMethod,
        string $idempotencyKey,
    ): PaymentIntent;
}
