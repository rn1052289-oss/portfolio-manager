<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Models\Payment;
use LogicException;
use Stripe\PaymentIntent;

final class PaymentIntentSynchronizer
{
    /**
     * StripeのPaymentIntent状態をLaravelのPaymentへ反映する。
     */
    public function sync(
        Payment $payment,
        PaymentIntent $paymentIntent,
    ): Payment {
        /*
         * 別のPaymentIntentを誤って別のPaymentへ反映することを防ぐ。
         */
        if ($payment->stripe_payment_intent_id !== $paymentIntent->id) {
            throw new LogicException(
                'PaymentとStripe PaymentIntentのIDが一致しません。'
            );
        }

        $status = (string) $paymentIntent->status;

        $failureMessage = data_get(
            $paymentIntent,
            'last_payment_error.message'
        );

        $attributes = [
            'status' => $status,
            'failure_message' => is_string($failureMessage)
                ? $failureMessage
                : null,
        ];

        /*
         * 初回成功時だけ決済完了日時を保存する。
         * 再同期で元の日時が上書きされないようにする。
         */
        if ($status === 'succeeded' && $payment->paid_at === null) {
            $attributes['paid_at'] = now();
        }

        /*
         * 初回キャンセル時だけキャンセル日時を保存する。
         */
        if ($status === 'canceled' && $payment->canceled_at === null) {
            $attributes['canceled_at'] = now();
        }

        $payment->fill($attributes);
        $payment->save();

        return $payment->refresh();
    }
}
