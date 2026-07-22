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
     * PaymentIntent作成時に渡された引数です。
     *
     * @var array<int, array{
     *     amount: int,
     *     currency: string,
     *     description: string|null,
     *     metadata: array<string, string>,
     *     idempotency_key: string
     * }>
     */
    private array $createPaymentIntentCalls = [];

    /**
     * PaymentIntent取得時に指定されたStripe IDです。
     *
     * @var array<int, string>
     */
    private array $retrievePaymentIntentIds = [];

    /**
     * PaymentIntent確定時に渡された引数です。
     *
     * @var array<int, array{
     *     payment_intent_id: string,
     *     payment_method: string,
     *     idempotency_key: string
     * }>
     */
    private array $confirmPaymentIntentCalls = [];

    /**
     * Fakeが返すPaymentIntentを設定します。
     */
    public function returnPaymentIntent(
        PaymentIntent $paymentIntent,
    ): self {
        $this->paymentIntentToReturn = $paymentIntent;

        return $this;
    }

    /**
     * PaymentIntent作成時の呼び出し履歴を返します。
     *
     * @return array<int, array{
     *     amount: int,
     *     currency: string,
     *     description: string|null,
     *     metadata: array<string, string>,
     *     idempotency_key: string
     * }>
     */
    public function createPaymentIntentCalls(): array
    {
        return $this->createPaymentIntentCalls;
    }

    /**
     * PaymentIntent取得時に指定されたStripe IDを返します。
     *
     * @return array<int, string>
     */
    public function retrievePaymentIntentIds(): array
    {
        return $this->retrievePaymentIntentIds;
    }

    /**
     * PaymentIntent確定時の呼び出し履歴を返します。
     *
     * @return array<int, array{
     *     payment_intent_id: string,
     *     payment_method: string,
     *     idempotency_key: string
     * }>
     */
    public function confirmPaymentIntentCalls(): array
    {
        return $this->confirmPaymentIntentCalls;
    }

    /**
     * Stripe残高取得は、現在のFeatureテストでは使用しません。
     */
    public function retrieveBalance(): Balance
    {
        throw new LogicException(
            'FakeStripeService::retrieveBalance()は設定されていません。'
        );
    }

    /**
     * PaymentIntent作成時に、事前設定したFakeの結果を返します。
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
        $this->createPaymentIntentCalls[] = [
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'metadata' => $metadata,
            'idempotency_key' => $idempotencyKey,
        ];

        return $this->paymentIntent();
    }

    /**
     * PaymentIntent取得時に、事前設定したFakeの結果を返します。
     */
    public function retrievePaymentIntent(
        string $paymentIntentId,
    ): PaymentIntent {
        $this->retrievePaymentIntentIds[] = $paymentIntentId;

        $paymentIntent = $this->paymentIntent();

        /*
         * Laravel側に保存されたStripe IDと、
         * Fakeへ設定したPaymentIntentのIDが一致するか確認します。
         */
        if ($paymentIntent->id !== $paymentIntentId) {
            throw new LogicException(
                '取得対象のPaymentIntent IDが一致しません。'
            );
        }

        return $paymentIntent;
    }

    /**
     * PaymentIntent確定時に、事前設定したFakeの結果を返します。
     */
    public function confirmPaymentIntent(
        string $paymentIntentId,
        string $paymentMethod,
        string $idempotencyKey,
    ): PaymentIntent {
        $this->confirmPaymentIntentCalls[] = [
            'payment_intent_id' => $paymentIntentId,
            'payment_method' => $paymentMethod,
            'idempotency_key' => $idempotencyKey,
        ];

        return $this->paymentIntent();
    }

    /**
     * 設定済みのPaymentIntentを取得します。
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
