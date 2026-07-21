<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    /**
     * 請求書一覧を表示できるか判定します。
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 請求書詳細を表示できるか判定します。
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice);
    }

    /**
     * 請求書を作成できるか判定します。
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 請求書を更新できるか判定します。
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice)
            && $invoice->status === Invoice::STATUS_DRAFT;
    }

    /**
     * 請求書を削除できるか判定します。
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice)
            && $invoice->status === Invoice::STATUS_DRAFT;
    }

    /**
     * 削除済み請求書を復元できるか判定します。
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * 請求書を完全削除できるか判定します。
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * 請求書を顧客へ送信できるか判定します。
     */
    public function send(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice)
            && in_array($invoice->status, [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_OVERDUE,
            ], true);
    }

    /**
     * 請求書の支払い処理を開始できるか判定します。
     */
    public function pay(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice)
            && in_array($invoice->status, [
                Invoice::STATUS_SENT,
                Invoice::STATUS_OVERDUE,
            ], true);
    }

    /**
     * 請求書をキャンセルできるか判定します。
     */
    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->ownsInvoice($user, $invoice)
            && in_array($invoice->status, [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_OVERDUE,
            ], true);
    }

    /**
     * ユーザーが請求書の所有者か判定します。
     */
    private function ownsInvoice(User $user, Invoice $invoice): bool
    {
        return $invoice->user_id === $user->id;
    }
}
