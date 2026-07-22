<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InvoiceMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * 請求書PDFを保存しているFilesystemディスクです。
     */
    private const PDF_DISK = 'local';

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $mailSubject,
        public readonly string $messageBody,
        public readonly string $pdfPath,
        public readonly string $pdfFileName
    ) {}

    /**
     * メールの件名を設定します。
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject
        );
    }

    /**
     * メール本文に使用するBladeとデータを設定します。
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice',
            with: [
                'invoice' => $this->invoice,
                'messageBody' => $this->messageBody,
            ]
        );
    }

    /**
     * 非公開Storageに保存した請求書PDFを添付します。
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(
                self::PDF_DISK,
                $this->pdfPath
            )
                ->as($this->pdfFileName)
                ->withMime('application/pdf'),
        ];
    }
}
