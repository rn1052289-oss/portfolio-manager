<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class InvoicePdfController extends Controller
{
    /**
     * 請求書PDFをブラウザ内で表示します。
     */
    public function preview(
        Invoice $invoice,
        InvoicePdfService $invoicePdfService
    ): Response|RedirectResponse {
        /*
         * 所有者本人だけがPDFを表示できます。
         *
         * PDFには住所・金額・振込先などが含まれるため、
         * ファイル生成前に必ず認可します。
         */
        $this->authorize('view', $invoice);

        $missingFields = $this->missingRequiredFields($invoice);

        if ($missingFields !== []) {
            return $this->redirectWithMissingFields(
                $invoice,
                $missingFields
            );
        }

        return $invoicePdfService
            ->make($invoice)
            ->stream(
                $invoicePdfService->downloadFileName($invoice)
            );
    }

    /**
     * 請求書PDFをファイルとしてダウンロードします。
     */
    public function download(
        Invoice $invoice,
        InvoicePdfService $invoicePdfService
    ): Response|RedirectResponse {
        /*
         * プレビューと同じ所有権ルールを使用します。
         */
        $this->authorize('view', $invoice);

        $missingFields = $this->missingRequiredFields($invoice);

        if ($missingFields !== []) {
            return $this->redirectWithMissingFields(
                $invoice,
                $missingFields
            );
        }

        return $invoicePdfService
            ->make($invoice)
            ->download(
                $invoicePdfService->downloadFileName($invoice)
            );
    }

    /**
     * PDF生成に必要な請求書情報の不足を調べます。
     *
     * 代表者名・電話番号・登録番号などは任意項目のため、
     * 必須確認には含めません。
     *
     * @return array<int, string>
     */
    private function missingRequiredFields(Invoice $invoice): array
    {
        $requiredFields = [
            '請求書番号' => $invoice->invoice_number,
            '請求書タイトル' => $invoice->title,
            '請求先名' => $invoice->billing_name,
            '発行者名' => $invoice->issuer_business_name,
            '発行者メールアドレス' => $invoice->issuer_email,
            '発行者郵便番号' => $invoice->issuer_postal_code,
            '発行者住所' => $invoice->issuer_address,
            '金融機関名' => $invoice->issuer_bank_name,
            '支店名' => $invoice->issuer_bank_branch_name,
            '口座種別' => $invoice->issuer_bank_account_type,
            '口座番号' => $invoice->issuer_bank_account_number,
            '口座名義' => $invoice->issuer_bank_account_holder,
            '発行日' => $invoice->issued_at,
            '支払期限' => $invoice->due_date,
            '取引日' => $invoice->transaction_date,
            '税率' => $invoice->tax_rate,
        ];

        $missingFields = [];

        foreach ($requiredFields as $label => $value) {
            if (blank($value)) {
                $missingFields[] = $label;
            }
        }

        return $missingFields;
    }

    /**
     * 不足項目を案内して請求書詳細へ戻します。
     *
     * @param  array<int, string>  $missingFields
     */
    private function redirectWithMissingFields(
        Invoice $invoice,
        array $missingFields
    ): RedirectResponse {
        return redirect()
            ->route('invoices.show', $invoice)
            ->with(
                'error',
                sprintf(
                    'PDF生成に必要な情報が不足しています：%s',
                    implode('、', $missingFields)
                )
            );
    }
}
