<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_preview_invoice_pdf(): void
    {
        $invoice = $this->createPdfReadyInvoice();

        $response = $this->get(
            route('invoices.pdf.preview', $invoice)
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_guest_cannot_download_invoice_pdf(): void
    {
        $invoice = $this->createPdfReadyInvoice();

        $response = $this->get(
            route('invoices.pdf.download', $invoice)
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_owner_can_preview_invoice_pdf(): void
    {
        $user = User::factory()->create();

        $invoice = $this->createPdfReadyInvoice(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('invoices.pdf.preview', $invoice)
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        /*
         * PDFファイルは「%PDF-」から始まります。
         *
         * HTMLエラー画面などが誤って返っていないことを
         * ファイルシグネチャから確認します。
         */
        $this->assertStringStartsWith(
            '%PDF-',
            (string) $response->getContent()
        );

        $contentDisposition = (string) $response
            ->headers
            ->get('content-disposition');

        /*
         * プレビューではブラウザ内表示を指示する
         * inlineヘッダーが必要です。
         */
        $this->assertStringContainsString(
            'inline',
            $contentDisposition
        );

        $this->assertStringContainsString(
            'invoice-INV-202607-0001.pdf',
            $contentDisposition
        );
    }

    public function test_owner_can_download_invoice_pdf(): void
    {
        $user = User::factory()->create();

        $invoice = $this->createPdfReadyInvoice(
            $user
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('invoices.pdf.download', $invoice)
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $this->assertStringStartsWith(
            '%PDF-',
            (string) $response->getContent()
        );

        $contentDisposition = (string) $response
            ->headers
            ->get('content-disposition');

        /*
         * ダウンロードでは保存を指示する
         * attachmentヘッダーが必要です。
         */
        $this->assertStringContainsString(
            'attachment',
            $contentDisposition
        );

        $this->assertStringContainsString(
            'invoice-INV-202607-0001.pdf',
            $contentDisposition
        );
    }

    public function test_user_cannot_preview_another_users_invoice_pdf(): void
    {
        $owner = User::factory()->create();

        $anotherUser = User::factory()->create();

        $invoice = $this->createPdfReadyInvoice(
            $owner
        );

        $response = $this
            ->actingAs($anotherUser)
            ->get(
                route('invoices.pdf.preview', $invoice)
            );

        $response->assertForbidden();
    }

    public function test_user_cannot_download_another_users_invoice_pdf(): void
    {
        $owner = User::factory()->create();

        $anotherUser = User::factory()->create();

        $invoice = $this->createPdfReadyInvoice(
            $owner
        );

        $response = $this
            ->actingAs($anotherUser)
            ->get(
                route('invoices.pdf.download', $invoice)
            );

        $response->assertForbidden();
    }

    public function test_pdf_generation_redirects_when_required_information_is_missing(): void
    {
        $user = User::factory()->create();

        $invoice = $this->createPdfReadyInvoice(
            $user,
            [
                /*
                 * PDF生成に必須としている発行者名を
                 * 意図的に未設定にします。
                 */
                'issuer_business_name' => null,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('invoices.pdf.preview', $invoice)
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHas(
                'error',
                'PDF生成に必要な情報が不足しています：発行者名'
            );
    }

    public function test_missing_invoice_pdf_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'invoices.pdf.preview',
                    [
                        'invoice' => 999999,
                    ]
                )
            );

        $response->assertNotFound();
    }

    /**
     * PDF生成に必要な情報が揃った請求書を作成します。
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createPdfReadyInvoice(
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
                'phone' => '03-1234-5678',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => 'LP制作',
            ]);

        $attributes = [
            'invoice_number' => 'INV-202607-0001',

            'title' => 'LP制作',

            'description' => 'LP制作・デザイン・コーディング',

            'subtotal' => 300000,
            'tax_amount' => 30000,
            'total_amount' => 330000,
            'currency' => 'jpy',

            /*
             * 請求先スナップショットです。
             */
            'billing_name' => '小田原漆器',
            'billing_contact_name' => '田中太郎',
            'billing_email' => 'trou@example.com',
            'billing_phone' => '03-1234-5678',
            'billing_postal_code' => '100-0001',
            'billing_address' => '東京都千代田区千代田1-1',

            /*
             * 発行者スナップショットです。
             */
            'issuer_business_name' => 'ClientWork Studio',

            'issuer_representative_name' => '山田 太郎',

            'issuer_email' => 'billing@example.com',

            'issuer_phone' => '03-1234-5678',

            'issuer_postal_code' => '100-0001',

            'issuer_address' => '東京都千代田区千代田1-1',

            'issuer_registration_number' => 'T1234567890123',

            'issuer_bank_name' => 'テスト銀行',

            'issuer_bank_branch_name' => '中央支店',

            'issuer_bank_account_type' => '普通',

            'issuer_bank_account_number' => '1234567',

            'issuer_bank_account_holder' => 'ヤマダ タロウ',

            /*
             * 取引日と税計算情報です。
             */
            'transaction_date' => '2026-07-21',
            'tax_rate' => Invoice::DEFAULT_TAX_RATE,
            'tax_rounding_method' => Invoice::TAX_ROUNDING_FLOOR,

            'status' => Invoice::STATUS_DRAFT,
            'issued_at' => '2026-07-21',
            'due_date' => '2026-07-28',
        ];

        return Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create(
                array_merge(
                    $attributes,
                    $overrides
                )
            );
    }
}
