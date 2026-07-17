<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    /**
     * ユーザーが指定された決済を閲覧できるか判定する。
     *
     * 決済入力画面・結果画面・履歴画面で共通利用する。
     */
    public function view(User $user, Payment $payment): bool
    {
        return $this->ownsPayment($user, $payment);
    }

    /**
     * ユーザーが指定された決済を確定できるか判定する。
     */
    public function confirm(User $user, Payment $payment): bool
    {
        return $this->ownsPayment($user, $payment);
    }

    /**
     * Paymentを所有するユーザーか判定する。
     */
    private function ownsPayment(User $user, Payment $payment): bool
    {
        return (int) $user->getKey() === (int) $payment->user_id;
    }
}
