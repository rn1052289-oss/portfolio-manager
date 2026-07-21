<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoiceShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは請求書詳細を表示できません。
     */
    public function test_guest_is_redirected_from_invoice_show_page(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->get(
            route('invoices.show', $invoice)
        );

        $response->assertRedirect(
            route('login')
        );
    }

    /**
     * 所有者は請求書詳細を表示できます。
     */
    public function test_owner_can_view_invoice_show_page(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '現在の顧客名',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => 'コーポレートサイト制作',
            ]);

        $invoice = Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create([
                'invoice_number' => 'INV-202607-0001',
                'title' => 'Webサイト制作費',
                'billing_name' => '株式会社請求先',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.show', $invoice));

        $response
            ->assertOk()
            ->assertViewIs('invoices.show')
            ->assertViewHas(
                'invoice',
                function (Invoice $viewInvoice) use ($invoice): bool {
                    return $viewInvoice->is($invoice)
                        && $viewInvoice->relationLoaded('project')
                        && $viewInvoice->relationLoaded('customer');
                }
            )
            ->assertSeeText('請求書詳細')
            ->assertSeeText('INV-202607-0001')
            ->assertSeeText('Webサイト制作費')
            ->assertSeeText('株式会社請求先')
            ->assertSeeText('コーポレートサイト制作')
            ->assertSeeText('¥110,000');
    }

    /**
     * 他ユーザーは請求書詳細を表示できません。
     */
    public function test_user_cannot_view_another_users_invoice(): void
    {
        $owner = User::factory()->create();

        $customer = Customer::factory()
            ->for($owner)
            ->create();

        $project = Project::factory()
            ->for($owner)
            ->for($customer)
            ->create();

        $invoice = Invoice::factory()
            ->for($owner)
            ->for($project)
            ->for($customer)
            ->create();

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->get(route('invoices.show', $invoice));

        $response->assertForbidden();
    }

    /**
     * 存在しない請求書IDでは404になります。
     */
    public function test_invoice_show_page_returns_not_found_for_missing_invoice(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/invoices/999999');

        $response->assertNotFound();
    }

    /**
     * 顧客の現在情報ではなく請求書作成時の
     * スナップショット情報が表示されます。
     */
    public function test_invoice_show_page_displays_billing_snapshot(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '変更後の顧客名',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create([
                'billing_name' => '請求書作成時の顧客名',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.show', $invoice));

        $response
            ->assertOk()
            ->assertSeeText('請求書作成時の顧客名')
            ->assertDontSeeText('変更後の顧客名');
    }
}
