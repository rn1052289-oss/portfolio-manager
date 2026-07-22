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

        $missingFields = $invoicePdfService
            ->missingRequiredFields($invoice);

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

        $missingFields = $invoicePdfService
            ->missingRequiredFields($invoice);

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
