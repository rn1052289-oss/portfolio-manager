<?php

namespace App\Services\Stripe;

use App\Models\Invoice;
use App\Models\Payment;
use LogicException;

class InvoicePaymentStatusSynchronizer
{
    private const PAYMENT_STATUS_SUCCEEDED = 'succeeded';

    private const INVOICE_STATUS_DRAFT = 'draft';

    private const INVOICE_STATUS_SENT = 'sent';

    private const INVOICE_STATUS_PAID = 'paid';

    private const INVOICE_STATUS_OVERDUE = 'overdue';

    private const INVOICE_STATUS_CANCELED = 'canceled';

    /**
     * Paymentの決済状態を、紐づくInvoiceへ反映する。
     *
     * Invoiceの行ロックを有効にするため、
     * 既存のDBトランザクション内から呼び出すこと。
     */
    public function sync(Payment $payment): ?Invoice
    {
        if ($payment->invoice_id === null) {
            return null;
        }

        $invoice = Invoice::query()
            ->whereKey($payment->invoice_id)
            ->lockForUpdate()
            ->firstOrFail();

        $this->assertPaymentMatchesInvoice(
            payment: $payment,
            invoice: $invoice,
        );

        if ($payment->status !== self::PAYMENT_STATUS_SUCCEEDED) {
            return $invoice;
        }

        if ($invoice->status === self::INVOICE_STATUS_PAID) {
            return $invoice;
        }

        if (
            in_array(
                $invoice->status,
                [
                    self::INVOICE_STATUS_DRAFT,
                    self::INVOICE_STATUS_CANCELED,
                ],
                true
            )
        ) {
            throw new LogicException(
                '支払い成功を反映できない請求書ステータスです。'
            );
        }

        if (
            ! in_array(
                $invoice->status,
                [
                    self::INVOICE_STATUS_SENT,
                    self::INVOICE_STATUS_OVERDUE,
                ],
                true
            )
        ) {
            throw new LogicException(
                '未対応の請求書ステータスが指定されています。'
            );
        }

        $invoice->forceFill([
            'status' => self::INVOICE_STATUS_PAID,
            'paid_at' => $payment->paid_at ?? now(),
        ])->save();

        return $invoice;
    }

    /**
     * Paymentが、更新対象のInvoiceと正しく対応しているか確認する。
     */
    private function assertPaymentMatchesInvoice(
        Payment $payment,
        Invoice $invoice
    ): void {
        if ((int) $payment->invoice_id !== (int) $invoice->getKey()) {
            throw new LogicException(
                'PaymentとInvoiceの紐付けが一致しません。'
            );
        }

        if ((int) $payment->user_id !== (int) $invoice->user_id) {
            throw new LogicException(
                'PaymentとInvoiceの所有者が一致しません。'
            );
        }

        if (
            $this->normalizeNullableId($payment->project_id)
            !== $this->normalizeNullableId($invoice->project_id)
        ) {
            throw new LogicException(
                'PaymentとInvoiceの案件が一致しません。'
            );
        }

        if ((int) $payment->amount !== (int) $invoice->total_amount) {
            throw new LogicException(
                'PaymentとInvoiceの金額が一致しません。'
            );
        }

        if (
            strtolower((string) $payment->currency)
            !== strtolower((string) $invoice->currency)
        ) {
            throw new LogicException(
                'PaymentとInvoiceの通貨が一致しません。'
            );
        }
    }

    /**
     * nullableな外部キーを比較しやすい形へ統一する。
     */
    private function normalizeNullableId(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }
}
