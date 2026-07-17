<?php

declare(strict_types=1);

namespace App\Contracts\Stripe;

use Stripe\Balance;
use Stripe\PaymentIntent;

interface StripeServiceInterface
{
    /**
     * Stripeアカウントの残高情報を取得する。
     */
    public function retrieveBalance(): Balance;

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
    ): PaymentIntent;

    /**
     * 作成済みのPaymentIntentをStripeから取得する。
     */
    public function retrievePaymentIntent(
        string $paymentIntentId,
    ): PaymentIntent;

    /**
     * PaymentIntentへ支払い方法を設定し、決済を確定する。
     */
    public function confirmPaymentIntent(
        string $paymentIntentId,
        string $paymentMethod,
        string $idempotencyKey,
    ): PaymentIntent;
}
