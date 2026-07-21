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
