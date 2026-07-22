<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InvoiceMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_invoice_mail_form(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->get(
            route('invoices.mail.create', $invoice)
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_owner_can_view_invoice_mail_form_with_defaults(): void
    {
        $user = User::factory()->create();

        $invoice = $this->createInvoice($user);

        $response = $this
            ->actingAs($user)
            ->get(
                route('invoices.mail.create', $invoice)
            );

        $response
            ->assertOk()
            ->assertViewIs('invoices.send')
            ->assertViewHas('invoice', $invoice)
            ->assertViewHas(
                'recipientEmail',
                'trou@example.com'
            )
            ->assertViewHas(
                'mailSubject',
                '【請求書】INV-202607-0001 LP制作'
            )
            ->assertViewHas(
                'messageBody',
                fn (string $body): bool => str_contains(
                    $body,
                    '2026年7月28日'
                )
            )
            ->assertSee('PDFを添付して送信');
    }

    public function test_user_cannot_access_another_users_invoice_mail(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $invoice = $this->createInvoice($owner);

        $this
            ->actingAs($anotherUser)
            ->get(
                route('invoices.mail.create', $invoice)
            )
            ->assertForbidden();

        $this
            ->actingAs($anotherUser)
            ->post(
                route('invoices.mail.store', $invoice),
                $this->validPayload()
            )
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_owner_can_send_draft_invoice_with_pdf(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $invoice = $this->createInvoice($user);

        $payload = $this->validPayload();

        $expectedPath = sprintf(
            'private/invoices/%d/invoice-INV-202607-0001.pdf',
            $user->id
        );

        $expectedFileName =
            'invoice-INV-202607-0001.pdf';

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.mail.store', $invoice),
                $payload
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHas(
                'success',
                '請求書をメールで送信しました。'
            );

        $invoice->refresh();

        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status
        );

        $this->assertNotNull($invoice->sent_at);

        $this->assertSame(
            $payload['recipient_email'],
            $invoice->last_sent_to
        );

        $this->assertSame(
            1,
            $invoice->send_count
        );

        $this->assertSame(
            $expectedPath,
            $invoice->pdf_path
        );

        Storage::disk('local')
            ->assertExists($expectedPath);

        $this->assertStringStartsWith(
            '%PDF-',
            Storage::disk('local')->get($expectedPath)
        );

        Mail::assertSent(
            InvoiceMail::class,
            function (InvoiceMail $mail) use ($invoice, $payload, $expectedPath, $expectedFileName): bool {
                $mail->assertTo(
                    $payload['recipient_email']
                );

                $mail->assertHasSubject(
                    $payload['subject']
                );

                $mail->assertHasAttachment(
                    Attachment::fromStorageDisk(
                        'local',
                        $expectedPath
                    )
                        ->as($expectedFileName)
                        ->withMime('application/pdf')
                );

                return $mail->invoice->is($invoice)
                    && $mail->messageBody === $payload['body']
                    && $mail->pdfPath === $expectedPath
                    && $mail->pdfFileName === $expectedFileName;
            }
        );

        Mail::assertSent(
            InvoiceMail::class,
            1
        );
    }

    public function test_sent_invoice_can_be_resent(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $invoice = $this->createInvoice(
            $user,
            [
                'status' => Invoice::STATUS_SENT,
                'sent_at' => now()->subDay(),
                'last_sent_to' => 'old@example.com',
                'send_count' => 1,
            ]
        );

        $payload = $this->validPayload([
            'recipient_email' => 'new@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.mail.store', $invoice),
                $payload
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHas(
                'success',
                '請求書をメールで再送しました。'
            );

        $invoice->refresh();

        $this->assertSame(
            Invoice::STATUS_SENT,
            $invoice->status
        );

        $this->assertSame(
            2,
            $invoice->send_count
        );

        $this->assertSame(
            'new@example.com',
            $invoice->last_sent_to
        );

        Mail::assertSent(
            InvoiceMail::class,
            1
        );
    }

    public function test_overdue_invoice_remains_overdue_after_resend(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $invoice = $this->createInvoice(
            $user,
            [
                'status' => Invoice::STATUS_OVERDUE,
                'sent_at' => now()->subMonth(),
                'last_sent_to' => 'old@example.com',
                'send_count' => 1,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.mail.store', $invoice),
                $this->validPayload()
            );

        $response->assertRedirect(
            route('invoices.show', $invoice)
        );

        $invoice->refresh();

        $this->assertSame(
            Invoice::STATUS_OVERDUE,
            $invoice->status
        );

        $this->assertSame(
            2,
            $invoice->send_count
        );

        Mail::assertSent(
            InvoiceMail::class,
            1
        );
    }

    public function test_sendable_invoice_statuses_include_online_payment_link(): void
    {
        Mail::fake();
        Storage::fake('local');

        $sendableStatuses = [
            Invoice::STATUS_DRAFT,
            Invoice::STATUS_SENT,
            Invoice::STATUS_OVERDUE,
        ];

        foreach ($sendableStatuses as $status) {
            /*
             * customersテーブルのユニーク制約との競合を避けるため、
             * ステータスごとに別ユーザーを作成します。
             */
            $user = User::factory()->create();

            $invoice = $this->createInvoice(
                $user,
                [
                    'status' => $status,
                    'sent_at' => $status === Invoice::STATUS_DRAFT
                        ? null
                        : now()->subDay(),
                    'last_sent_to' => $status === Invoice::STATUS_DRAFT
                        ? null
                        : 'old@example.com',
                    'send_count' => $status === Invoice::STATUS_DRAFT
                        ? 0
                        : 1,
                ]
            );

            $expectedPaymentUrl = route(
                'invoice-payments.show',
                [
                    'publicToken' => $invoice->public_token,
                ]
            );

            $this
                ->actingAs($user)
                ->post(
                    route('invoices.mail.store', $invoice),
                    $this->validPayload()
                )
                ->assertRedirect(
                    route('invoices.show', $invoice)
                );

            Mail::assertSent(
                InvoiceMail::class,
                function (InvoiceMail $mail) use ($invoice, $expectedPaymentUrl): bool {
                    /*
                     * ループ内で過去に送信した別請求書のMailableは、
                     * 本文検証を行わずに対象外とします。
                     */
                    if (! $mail->invoice->is($invoice)) {
                        return false;
                    }

                    $this->assertSame(
                        $expectedPaymentUrl,
                        $mail->paymentUrl
                    );

                    $mail->assertSeeInHtml(
                        'オンラインで支払う'
                    );

                    $mail->assertSeeInHtml(
                        $expectedPaymentUrl
                    );

                    $mail->assertSeeInText(
                        'オンラインでのお支払い'
                    );

                    $mail->assertSeeInText(
                        $expectedPaymentUrl
                    );

                    $mail->assertDontSeeInHtml(
                        'client_secret'
                    );

                    $mail->assertDontSeeInText(
                        'client_secret'
                    );

                    return true;
                }
            );
        }

        Mail::assertSent(
            InvoiceMail::class,
            3
        );
    }

    public function test_invoice_mail_without_payment_url_hides_payment_cta(): void
    {
        Storage::fake('local');

        $invoice = $this->createInvoice(
            null,
            [
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]
        );

        $pdfPath = sprintf(
            'private/invoices/%d/test-invoice.pdf',
            $invoice->user_id
        );

        Storage::disk('local')->put(
            $pdfPath,
            '%PDF-1.4 test'
        );

        $unexpectedPaymentUrl = route(
            'invoice-payments.show',
            [
                'publicToken' => $invoice->public_token,
            ]
        );

        $mail = new InvoiceMail(
            invoice: $invoice,
            mailSubject: '請求書のご案内',
            messageBody: '請求書をお送りします。',
            pdfPath: $pdfPath,
            pdfFileName: 'test-invoice.pdf',
            paymentUrl: null
        );

        $mail->assertDontSeeInHtml(
            'オンラインで支払う'
        );

        $mail->assertDontSeeInHtml(
            $unexpectedPaymentUrl
        );

        $mail->assertDontSeeInText(
            'オンラインでのお支払い'
        );

        $mail->assertDontSeeInText(
            $unexpectedPaymentUrl
        );
    }

    public function test_paid_and_canceled_invoices_cannot_be_sent(): void
    {
        Mail::fake();
        Storage::fake('local');

        foreach (
            [
                Invoice::STATUS_PAID,
                Invoice::STATUS_CANCELED,
            ] as $status
        ) {
            /*
             * customersテーブルには
             * user_idとnameのユニーク制約があるため、
             * ステータスごとに別ユーザーを作成します。
             */
            $user = User::factory()->create();

            $invoice = $this->createInvoice(
                $user,
                [
                    'status' => $status,
                ]
            );

            $this
                ->actingAs($user)
                ->get(
                    route('invoices.mail.create', $invoice)
                )
                ->assertForbidden();

            $this
                ->actingAs($user)
                ->post(
                    route('invoices.mail.store', $invoice),
                    $this->validPayload()
                )
                ->assertForbidden();
        }

        Mail::assertNothingSent();
    }

    public function test_send_request_rejects_invalid_and_managed_fields(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $invoice = $this->createInvoice($user);

        $formUrl = route(
            'invoices.mail.create',
            $invoice
        );

        $response = $this
            ->actingAs($user)
            ->from($formUrl)
            ->post(
                route('invoices.mail.store', $invoice),
                [
                    'recipient_email' => 'invalid-email',
                    'subject' => '   ',
                    'body' => '   ',
                    'status' => Invoice::STATUS_PAID,
                    'send_count' => 999,
                    'pdf_path' => 'tampered.pdf',
                ]
            );

        $response
            ->assertRedirect($formUrl)
            ->assertSessionHasErrors([
                'recipient_email',
                'subject',
                'body',
                'status',
                'send_count',
                'pdf_path',
            ]);

        $invoice->refresh();

        $this->assertSame(
            Invoice::STATUS_DRAFT,
            $invoice->status
        );

        $this->assertSame(
            0,
            $invoice->send_count
        );

        $this->assertNull($invoice->pdf_path);

        Mail::assertNothingSent();
    }

    public function test_send_redirects_when_pdf_information_is_missing(): void
    {
        Mail::fake();
        Storage::fake('local');

        $user = User::factory()->create();

        $invoice = $this->createInvoice(
            $user,
            [
                'issuer_business_name' => null,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.mail.store', $invoice),
                $this->validPayload()
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHas(
                'error',
                'メール添付用PDFの生成に必要な情報が不足しています：発行者名'
            );

        $invoice->refresh();

        $this->assertSame(
            Invoice::STATUS_DRAFT,
            $invoice->status
        );

        $this->assertSame(
            0,
            $invoice->send_count
        );

        $this->assertNull($invoice->sent_at);
        $this->assertNull($invoice->pdf_path);

        Mail::assertNothingSent();
    }

    public function test_missing_invoice_mail_form_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'invoices.mail.create',
                    [
                        'invoice' => 999999,
                    ]
                )
            );

        $response->assertNotFound();
    }

    /**
     * メール送信に使用する正常な入力値です。
     *
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'recipient_email' => 'recipient@example.com',
                'subject' => '【請求書】INV-202607-0001 LP制作',
                'body' => implode("\n", [
                    '平素よりお世話になっております。',
                    '',
                    '請求書をお送りします。',
                ]),
            ],
            $overrides
        );
    }

    /**
     * PDF生成に必要な情報が揃った請求書を作成します。
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(
        ?User $user = null,
        array $overrides = []
    ): Invoice {
        $user ??= User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '小田原漆器',
                'contact_name' => '田中太郎',
                'email' => 'trou@example.com',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => 'LP制作',
            ]);

        return Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create(
                array_merge(
                    [
                        'invoice_number' => 'INV-202607-0001',
                        'title' => 'LP制作',
                        'billing_name' => '小田原漆器',
                        'billing_contact_name' => '田中太郎',
                        'billing_email' => 'trou@example.com',
                        'issued_at' => '2026-07-21',
                        'due_date' => '2026-07-28',
                        'transaction_date' => '2026-07-21',
                    ],
                    $overrides
                )
            );
    }
}
