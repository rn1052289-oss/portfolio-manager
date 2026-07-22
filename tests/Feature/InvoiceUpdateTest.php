<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoiceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_draft_invoice(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext();

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'title' => '更新後タイトル',
                        'description' => '更新後の請求内容',
                        'subtotal' => 120000,
                        'tax_amount' => 12000,
                        'total_amount' => 132000,
                    ]
                )
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'title' => '更新後タイトル',
            'description' => '更新後の請求内容',
            'subtotal' => 120000,
            'tax_amount' => 12000,
            'total_amount' => 132000,
        ]);
    }

    public function test_update_recalculates_tax_and_total_on_server(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext();

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'subtotal' => 100000,

                        /*
                         * リクエスト上は整合しているが、
                         * 実際の10％計算とは異なる値を送信します。
                         */
                        'tax_amount' => 0,
                        'total_amount' => 100000,
                    ]
                )
            );

        $response->assertRedirect(
            route('invoices.show', $invoice)
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 100000,
            'tax_amount' => 10000,
            'total_amount' => 110000,
        ]);
    }

    public function test_update_refreshes_customer_snapshot(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext();

        $customer->update([
            'name' => '更新後株式会社',
            'contact_name' => '更新後担当者',
            'email' => 'updated@example.com',
            'phone' => '03-9999-9999',
            'postal_code' => '100-0001',
            'address' => '東京都千代田区更新後1-1',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer
                )
            );

        $response->assertRedirect(
            route('invoices.show', $invoice)
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'billing_name' => '更新後株式会社',
            'billing_contact_name' => '更新後担当者',
            'billing_email' => 'updated@example.com',
            'billing_phone' => '03-9999-9999',
            'billing_postal_code' => '100-0001',
            'billing_address' => '東京都千代田区更新後1-1',
        ]);
    }

    public function test_update_refreshes_business_profile_snapshot_and_tax_information(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext([
                /*
                 * 更新前の古い発行者スナップショットです。
                 */
                'issuer_business_name' => '更新前の屋号',
                'issuer_representative_name' => '更新前 代表者',
                'issuer_email' => 'before@example.com',
                'issuer_phone' => '03-0000-0000',
                'issuer_postal_code' => '100-0001',
                'issuer_address' => '東京都千代田区更新前1-1',
                'issuer_registration_number' => 'T1111111111111',
                'issuer_bank_name' => '更新前銀行',
                'issuer_bank_branch_name' => '更新前支店',
                'issuer_bank_account_type' => '当座',
                'issuer_bank_account_number' => '1111111',
                'issuer_bank_account_holder' => 'コウシンマエ メイギ',

                /*
                 * 更新処理によって現在の仕様へ置き換わる値です。
                 */
                'transaction_date' => '2026-07-10',
                'tax_rate' => 8,
                'tax_rounding_method' => 'ceil',
            ]);

        $businessProfile = $user
            ->businessProfile()
            ->create([
                'business_name' => '更新後の屋号',
                'representative_name' => '更新後 代表者',
                'email' => 'issuer-updated@example.com',
                'phone' => '03-1234-5678',
                'postal_code' => '150-0001',
                'address' => '東京都渋谷区更新後1-1',
                'invoice_registration_number' => 'T1234567890123',
                'bank_name' => '更新後銀行',
                'bank_branch_name' => '中央支店',
                'bank_account_type' => '普通',
                'bank_account_number' => '1234567',
                'bank_account_holder' => 'コウシンゴ メイギ',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'issued_at' => '2026-07-25',
                        'due_date' => '2026-08-25',
                    ]
                )
            );

        $response
            ->assertRedirect(
                route('invoices.show', $invoice)
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'success',
                '請求書を更新しました。'
            );

        /*
         * DBから更新後の請求書を再取得します。
         */
        $updatedInvoice = $invoice->fresh();

        $this->assertNotNull(
            $updatedInvoice
        );

        $this->assertSame(
            '更新後の屋号',
            $updatedInvoice->issuer_business_name
        );

        $this->assertSame(
            '更新後 代表者',
            $updatedInvoice->issuer_representative_name
        );

        $this->assertSame(
            'issuer-updated@example.com',
            $updatedInvoice->issuer_email
        );

        $this->assertSame(
            '03-1234-5678',
            $updatedInvoice->issuer_phone
        );

        $this->assertSame(
            '150-0001',
            $updatedInvoice->issuer_postal_code
        );

        $this->assertSame(
            '東京都渋谷区更新後1-1',
            $updatedInvoice->issuer_address
        );

        $this->assertSame(
            'T1234567890123',
            $updatedInvoice->issuer_registration_number
        );

        $this->assertSame(
            '更新後銀行',
            $updatedInvoice->issuer_bank_name
        );

        $this->assertSame(
            '中央支店',
            $updatedInvoice->issuer_bank_branch_name
        );

        $this->assertSame(
            '普通',
            $updatedInvoice->issuer_bank_account_type
        );

        $this->assertSame(
            '1234567',
            $updatedInvoice->issuer_bank_account_number
        );

        $this->assertSame(
            'コウシンゴ メイギ',
            $updatedInvoice->issuer_bank_account_holder
        );

        /*
         * 現在の仕様では、取引日は更新後の発行日と同じです。
         */
        $this->assertSame(
            '2026-07-25',
            $updatedInvoice->getRawOriginal(
                'transaction_date'
            )
        );

        $this->assertSame(
            Invoice::DEFAULT_TAX_RATE,
            $updatedInvoice->tax_rate
        );

        $this->assertSame(
            Invoice::TAX_ROUNDING_FLOOR,
            $updatedInvoice->tax_rounding_method
        );

        /*
         * Invoice側の口座番号が平文保存されていないことを
         * DBの生値から確認します。
         */
        $rawAccountNumber = $updatedInvoice->getRawOriginal(
            'issuer_bank_account_number'
        );

        $this->assertIsString(
            $rawAccountNumber
        );

        $this->assertNotSame(
            '1234567',
            $rawAccountNumber
        );

        $this->assertGreaterThan(
            strlen('1234567'),
            strlen($rawAccountNumber)
        );

        /*
         * 請求書更新後にBusinessProfileを変更しても、
         * 保存済みのInvoiceスナップショットは変化しません。
         */
        $businessProfile->update([
            'business_name' => 'さらに変更した屋号',
            'bank_name' => 'さらに変更した銀行',
            'bank_account_number' => '7654321',
        ]);

        $snapshotInvoice = $updatedInvoice->fresh();

        $this->assertNotNull(
            $snapshotInvoice
        );

        $this->assertSame(
            '更新後の屋号',
            $snapshotInvoice->issuer_business_name
        );

        $this->assertSame(
            '更新後銀行',
            $snapshotInvoice->issuer_bank_name
        );

        $this->assertSame(
            '1234567',
            $snapshotInvoice->issuer_bank_account_number
        );
    }

    public function test_update_rejects_system_managed_issuer_and_tax_fields(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext([
                'title' => '改ざん前の請求書',
                'issuer_business_name' => '正規の屋号',
                'issuer_bank_name' => '正規の銀行',
                'issuer_bank_account_number' => '1234567',
                'transaction_date' => '2026-07-10',
                'tax_rate' => Invoice::DEFAULT_TAX_RATE,
                'tax_rounding_method' => Invoice::TAX_ROUNDING_FLOOR,
            ]);

        $systemManagedFields = [
            'issuer_business_name' => '改ざんされた屋号',
            'issuer_representative_name' => '改ざんされた代表者',
            'issuer_email' => 'attacker@example.com',
            'issuer_phone' => '090-9999-9999',
            'issuer_postal_code' => '999-9999',
            'issuer_address' => '改ざんされた住所',
            'issuer_registration_number' => 'T9999999999999',
            'issuer_bank_name' => '改ざん銀行',
            'issuer_bank_branch_name' => '改ざん支店',
            'issuer_bank_account_type' => '当座',
            'issuer_bank_account_number' => '9999999',
            'issuer_bank_account_holder' => 'カイザン メイギ',
            'transaction_date' => '2020-01-01',
            'tax_rate' => 0,
            'tax_rounding_method' => 'ceil',
        ];

        $payload = array_merge(
            $this->validPayload(
                $project,
                $customer,
                [
                    'title' => '改ざん後のタイトル',
                ]
            ),
            $systemManagedFields
        );

        $response = $this
            ->actingAs($user)
            ->from(
                route('invoices.edit', $invoice)
            )
            ->put(
                route('invoices.update', $invoice),
                $payload
            );

        $response
            ->assertRedirect(
                route('invoices.edit', $invoice)
            )
            ->assertSessionHasErrors(
                array_keys($systemManagedFields)
            );

        $unchangedInvoice = $invoice->fresh();

        $this->assertNotNull(
            $unchangedInvoice
        );

        /*
         * バリデーションで拒否されたため、
         * 通常の編集項目も保存されません。
         */
        $this->assertSame(
            '改ざん前の請求書',
            $unchangedInvoice->title
        );

        $this->assertSame(
            '正規の屋号',
            $unchangedInvoice->issuer_business_name
        );

        $this->assertSame(
            '正規の銀行',
            $unchangedInvoice->issuer_bank_name
        );

        $this->assertSame(
            '1234567',
            $unchangedInvoice->issuer_bank_account_number
        );

        $this->assertSame(
            '2026-07-10',
            $unchangedInvoice->getRawOriginal(
                'transaction_date'
            )
        );

        $this->assertSame(
            Invoice::DEFAULT_TAX_RATE,
            $unchangedInvoice->tax_rate
        );

        $this->assertSame(
            Invoice::TAX_ROUNDING_FLOOR,
            $unchangedInvoice->tax_rounding_method
        );
    }

    public function test_invoice_number_is_preserved_when_issued_month_is_unchanged(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext([
                'invoice_number' => 'INV-202607-0042',
                'issued_at' => '2026-07-10',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'issued_at' => '2026-07-25',
                        'due_date' => '2026-08-25',
                    ]
                )
            );

        $response->assertRedirect(
            route('invoices.show', $invoice)
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => 'INV-202607-0042',
            'issued_at' => '2026-07-25',
        ]);
    }

    public function test_invoice_number_is_regenerated_when_issued_month_changes(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext([
                'invoice_number' => 'INV-202607-0001',
                'issued_at' => '2026-07-10',
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'issued_at' => '2026-08-01',
                        'due_date' => '2026-08-31',
                    ]
                )
            );

        $response->assertRedirect(
            route('invoices.show', $invoice)
        );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => 'INV-202608-0001',
            'issued_at' => '2026-08-01',
        ]);
    }

    public function test_user_cannot_update_another_users_invoice(): void
    {
        [$owner, $customer, $project, $invoice] =
            $this->createInvoiceContext();

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer
                )
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'user_id' => $owner->id,
            'title' => $invoice->title,
        ]);
    }

    public function test_sent_invoice_cannot_be_updated(): void
    {
        [$user, $customer, $project, $invoice] =
            $this->createInvoiceContext([
                'status' => Invoice::STATUS_SENT,
                'sent_at' => now(),
            ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('invoices.update', $invoice),
                $this->validPayload(
                    $project,
                    $customer,
                    [
                        'title' => '変更してはいけないタイトル',
                    ]
                )
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->id,
            'title' => '変更してはいけないタイトル',
        ]);
    }

    /**
     * @param  array<string, mixed>  $invoiceAttributes
     * @return array{User, Customer, Project, Invoice}
     */
    private function createInvoiceContext(
        array $invoiceAttributes = []
    ): array {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create(array_merge([
                'invoice_number' => 'INV-202607-0001',
                'issued_at' => '2026-07-10',
                'due_date' => '2026-08-10',
                'status' => Invoice::STATUS_DRAFT,
            ], $invoiceAttributes));

        return [
            $user,
            $customer,
            $project,
            $invoice,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        Project $project,
        Customer $customer,
        array $overrides = []
    ): array {
        return array_merge([
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => '更新後の請求書',
            'description' => '更新後の説明',
            'subtotal' => 100000,
            'tax_amount' => 10000,
            'total_amount' => 110000,
            'issued_at' => '2026-07-20',
            'due_date' => '2026-08-20',
        ], $overrides);
    }
}
