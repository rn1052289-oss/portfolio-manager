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
     * PDF生成に必要な請求書情報の不足を調べます。
     *
     * 代表者名・電話番号・登録番号などは任意項目のため、
     * 必須確認には含めません。
     *
     * @return array<int, string>
     */
    public function missingRequiredFields(Invoice $invoice): array
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
