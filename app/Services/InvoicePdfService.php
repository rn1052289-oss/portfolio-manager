<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class InvoicePdfService
{
    /**
     * PDFを保存するFilesystemディスクです。
     */
    private const DISK = 'local';

    /**
     * localディスク内の非公開保存ディレクトリです。
     */
    private const DIRECTORY = 'private/invoices';

    /**
     * 請求書PDFをメモリ上に生成します。
     */
    public function make(Invoice $invoice): PdfDocument
    {
        return PdfFacade::loadView(
            'invoices.pdf',
            [
                'invoice' => $invoice,
            ]
        )
            ->setOption(
                'defaultFont',
                'IPAPGothic'
            )
            ->setPaper(
                'a4',
                'portrait'
            );
    }

    /**
     * 請求書PDFを非公開Storageへ保存します。
     *
     * 戻り値はlocalディスクを基準にした相対パスです。
     */
    public function store(Invoice $invoice): string
    {
        $path = $this->storagePath($invoice);

        $stored = $this->disk()->put(
            $path,
            $this->make($invoice)->output()
        );

        if (! $stored) {
            throw new RuntimeException(
                '請求書PDFの保存に失敗しました。'
            );
        }

        return $path;
    }

    /**
     * ブラウザへ返すPDFファイル名を生成します。
     */
    public function downloadFileName(Invoice $invoice): string
    {
        return sprintf(
            'invoice-%s.pdf',
            $this->safeInvoiceNumber($invoice)
        );
    }

    /**
     * PDFの非公開保存先を生成します。
     *
     * ユーザーごとにディレクトリを分け、
     * 同じ請求書番号を持つ別ユーザーと衝突しないようにします。
     */
    private function storagePath(Invoice $invoice): string
    {
        return sprintf(
            '%s/%d/%s',
            self::DIRECTORY,
            $invoice->user_id,
            $this->downloadFileName($invoice)
        );
    }

    /**
     * PDF保存に使用するFilesystemを取得します。
     */
    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /**
     * 請求書番号を安全なファイル名へ変換します。
     */
    private function safeInvoiceNumber(Invoice $invoice): string
    {
        $invoiceNumber = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $invoice->invoice_number
        );

        $invoiceNumber = trim(
            (string) $invoiceNumber,
            '-_'
        );

        if ($invoiceNumber !== '') {
            return $invoiceNumber;
        }

        return sprintf(
            'invoice-%d',
            $invoice->getKey()
        );
    }
}
