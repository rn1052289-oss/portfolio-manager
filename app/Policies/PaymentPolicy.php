<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * ユーザーが指定された決済を確定できるか判定する。
     */
    public function confirm(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }
}
