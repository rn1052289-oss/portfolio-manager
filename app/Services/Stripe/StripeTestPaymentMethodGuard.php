<?php

declare(strict_types=1);

namespace App\Services\Stripe;

final class StripeTestPaymentMethodGuard
{
    /**
     * このAPIで使用を許可するStripeテスト用PaymentMethod。
     *
     * @var array<int, string>
     */
    private const ALLOWED_PAYMENT_METHODS = [
        'pm_card_visa',
    ];

    /**
     * 現在の環境で、指定されたテスト用PaymentMethodを
     * 安全に使用できるか判定する。
     */
    public function allows(string $paymentMethod): bool
    {
        $secret = config('services.stripe.secret');

        return app()->environment('local', 'testing')
            && is_string($secret)
            && str_starts_with($secret, 'sk_test_')
            && in_array(
                $paymentMethod,
                self::ALLOWED_PAYMENT_METHODS,
                true
            );
    }
}
