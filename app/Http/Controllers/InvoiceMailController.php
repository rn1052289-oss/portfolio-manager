<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SendInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

final class InvoiceMailController extends Controller
{
    /**
     * 請求書メールの送信画面を表示します。
     */
    public function create(
        Invoice $invoice,
        InvoicePdfService $invoicePdfService
    ): View|RedirectResponse {
        $this->authorize('send', $invoice);

        $missingFields = $invoicePdfService
            ->missingRequiredFields($invoice);

        if ($missingFields !== []) {
            return $this->redirectWithMissingFields(
                $invoice,
                $missingFields
            );
        }

        return view('invoices.send', [
            'invoice' => $invoice,
            'recipientEmail' => $invoice->billing_email,
            'mailSubject' => $this->defaultSubject($invoice),
            'messageBody' => $this->defaultMessageBody($invoice),
        ]);
    }

    /**
     * 請求書PDFを添付してメールを送信します。
     */
    public function store(
        SendInvoiceRequest $request,
        Invoice $invoice,
        InvoicePdfService $invoicePdfService
    ): RedirectResponse {
        $this->authorize('send', $invoice);

        $validated = $request->validated();

        try {
            $successMessage = DB::transaction(
                function () use ($invoice, $invoicePdfService, $validated): string {
                    /*
                     * 編集・キャンセル・決済処理などとの競合を避けるため、
                     * 送信対象の請求書を排他ロックして再取得します。
                     */
                    $lockedInvoice = Invoice::query()
                        ->whereKey($invoice->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                     * 最初の認可後にステータスが変わった可能性を考慮し、
                     * ロック取得後にもう一度送信権限を確認します。
                     */
                    $this->authorize('send', $lockedInvoice);

                    $missingFields = $invoicePdfService
                        ->missingRequiredFields($lockedInvoice);

                    if ($missingFields !== []) {
                        throw new MissingInvoicePdfFieldsException(
                            $missingFields
                        );
                    }

                    /*
                     * メールへ添付するPDFを非公開Storageへ保存します。
                     */
                    $pdfPath = $invoicePdfService
                        ->store($lockedInvoice);

                    $pdfFileName = $invoicePdfService
                        ->downloadFileName($lockedInvoice);

                    /*
                     * 初回送信時点ではInvoiceがdraftのため、
                     * メール送信後にsentへ更新されることを前提として
                     * 公開支払いURLを生成します。
                     *
                     * 再送時はsent・overdueだけに支払いURLを付与し、
                     * paid・canceledには付与しません。
                     */
                    $paymentUrl = in_array(
                        $lockedInvoice->status,
                        [
                            Invoice::STATUS_DRAFT,
                            Invoice::STATUS_SENT,
                            Invoice::STATUS_OVERDUE,
                        ],
                        true
                    )
                        ? route('invoice-payments.show', [
                            'publicToken' => $lockedInvoice->public_token,
                        ])
                        : null;

                    /*
                     * Mailableは件名・本文・添付・メール表示用データだけを担当し、
                     * Invoiceの更新はController側で行います。
                     */
                    Mail::to($validated['recipient_email'])
                        ->send(
                            new InvoiceMail(
                                invoice: $lockedInvoice,
                                mailSubject: $validated['subject'],
                                messageBody: $validated['body'],
                                pdfPath: $pdfPath,
                                pdfFileName: $pdfFileName,
                                paymentUrl: $paymentUrl
                            )
                        );

                    $isFirstSend = $lockedInvoice->isDraft();

                    /*
                     * メール送信に成功した後だけ、
                     * Invoiceの送信管理情報を更新します。
                     */
                    $lockedInvoice->fill([
                        'status' => $isFirstSend
                            ? Invoice::STATUS_SENT
                            : $lockedInvoice->status,
                        'sent_at' => now(),
                        'last_sent_to' => $validated['recipient_email'],
                        'send_count' => $lockedInvoice->send_count + 1,
                        'pdf_path' => $pdfPath,
                    ]);

                    $lockedInvoice->save();

                    return $isFirstSend
                        ? '請求書をメールで送信しました。'
                        : '請求書をメールで再送しました。';
                }
            );
        } catch (MissingInvoicePdfFieldsException $exception) {
            return $this->redirectWithMissingFields(
                $invoice,
                $exception->missingFields
            );
        } catch (
            AuthorizationException|ModelNotFoundException $exception
        ) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors([
                    'mail' => 'メールを送信できませんでした。時間をおいて再度お試しください。',
                ]);
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', $successMessage);
    }

    /**
     * メール件名の初期値を作成します。
     */
    private function defaultSubject(Invoice $invoice): string
    {
        return sprintf(
            '【請求書】%s %s',
            $invoice->invoice_number,
            $invoice->title
        );
    }

    /**
     * メール本文の初期値を作成します。
     */
    private function defaultMessageBody(Invoice $invoice): string
    {
        $dueDate = $invoice->due_date?->format('Y年n月j日')
            ?? '指定の期日';

        return implode("\n", [
            '平素よりお世話になっております。',
            '',
            sprintf(
                '請求書「%s」をお送りします。',
                $invoice->title
            ),
            sprintf(
                '添付PDFをご確認のうえ、%sまでにお支払いをお願いいたします。',
                $dueDate
            ),
            '',
            'ご不明な点がございましたら、お知らせください。',
        ]);
    }

    /**
     * PDF生成に必要な情報不足を案内します。
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
                    'メール添付用PDFの生成に必要な情報が不足しています：%s',
                    implode('、', $missingFields)
                )
            );
    }
}

/**
 * トランザクション内でPDF必須項目の不足を伝える例外です。
 */
final class MissingInvoicePdfFieldsException extends \RuntimeException
{
    /**
     * @param  array<int, string>  $missingFields
     */
    public function __construct(
        public readonly array $missingFields
    ) {
        parent::__construct(
            '請求書PDFの必須情報が不足しています。'
        );
    }
}
