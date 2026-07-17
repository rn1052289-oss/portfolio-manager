<?php

declare(strict_types=1);

namespace Tests\Fakes\Stripe;

use App\Contracts\Stripe\StripeServiceInterface;
use LogicException;
use Stripe\Balance;
use Stripe\PaymentIntent;

final class FakeStripeService implements StripeServiceInterface
{
    private ?PaymentIntent $paymentIntentToReturn = null;

    /**
     * Fakeが返すPaymentIntentを設定する。
     */
    public function returnPaymentIntent(
        PaymentIntent $paymentIntent,
    ): self {
        $this->paymentIntentToReturn = $paymentIntent;

        return $this;
    }

    /**
     * Stripe残高取得は、現在のFeatureテストでは使用しない。
     */
    public function retrieveBalance(): Balance
    {
        throw new LogicException(
            'FakeStripeService::retrieveBalance()は設定されていません。'
        );
    }

    /**
     * PaymentIntent作成時に、事前設定したFakeの結果を返す。
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
        return $this->paymentIntent();
    }

    /**
     * PaymentIntent確定時に、事前設定したFakeの結果を返す。
     */
    public function confirmPaymentIntent(
        string $paymentIntentId,
        string $paymentMethod,
        string $idempotencyKey,
    ): PaymentIntent {
        return $this->paymentIntent();
    }

    /**
     * 設定済みのPaymentIntentを取得する。
     */
    private function paymentIntent(): PaymentIntent
    {
        if (! $this->paymentIntentToReturn instanceof PaymentIntent) {
            throw new LogicException(
                'FakeStripeServiceへ返却するPaymentIntentが設定されていません。'
            );
        }

        return $this->paymentIntentToReturn;
    }
}
