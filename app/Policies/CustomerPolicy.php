<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * 顧客一覧を閲覧できるか判定します。
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 顧客詳細を閲覧できるか判定します。
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id;
    }

    /**
     * 顧客を登録できるか判定します。
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 顧客を更新できるか判定します。
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id;
    }

    /**
     * 顧客を削除できるか判定します。
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->id === $customer->user_id;
    }
}