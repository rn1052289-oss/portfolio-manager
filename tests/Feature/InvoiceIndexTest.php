<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

final class InvoiceIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは請求書一覧を表示できません。
     */
    public function test_guest_is_redirected_from_invoice_index(): void
    {
        $response = $this->get(route('invoices.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログインユーザーは請求書一覧を表示できます。
     */
    public function test_authenticated_user_can_view_invoice_index(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.index'));

        $response
            ->assertOk()
            ->assertViewIs('invoices.index')
            ->assertViewHas('invoices');
    }

    /**
     * 一覧にはログインユーザー本人の請求書だけが表示されます。
     */
    public function test_invoice_index_only_displays_invoices_owned_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $ownInvoice = Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create([
                'invoice_number' => 'INV-OWN-0001',
                'title' => '本人所有の請求書',
            ]);

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create();

        $otherProject = Project::factory()
            ->for($otherUser)
            ->for($otherCustomer)
            ->create();

        $otherInvoice = Invoice::factory()
            ->for($otherUser)
            ->for($otherProject)
            ->for($otherCustomer)
            ->create([
                'invoice_number' => 'INV-OTHER-0001',
                'title' => '他ユーザーの請求書',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.index'));

        $response
            ->assertOk()
            ->assertSeeText($ownInvoice->invoice_number)
            ->assertSeeText($ownInvoice->title)
            ->assertDontSeeText($otherInvoice->invoice_number)
            ->assertDontSeeText($otherInvoice->title);
    }

    /**
     * 請求書に紐づく案件・顧客・金額・ステータスが表示されます。
     */
    public function test_invoice_index_displays_invoice_related_information(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '株式会社テストクライアント',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => 'コーポレートサイト制作案件',
            ]);

        Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->sent()
            ->create([
                'invoice_number' => 'INV-202607-0001',
                'title' => 'Webサイト制作費',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.index'));

        $response
            ->assertOk()
            ->assertSeeText('INV-202607-0001')
            ->assertSeeText('Webサイト制作費')
            ->assertSeeText('コーポレートサイト制作案件')
            ->assertSeeText('株式会社テストクライアント')
            ->assertSeeText('送信済み')
            ->assertSeeText('¥110,000')
            ->assertSeeText('2026/07/21')
            ->assertSeeText('2026/08/31');
    }

    /**
     * 請求書一覧は1ページあたり10件に分割されます。
     */
    public function test_invoice_index_paginates_invoices_by_ten_items(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        Invoice::factory()
            ->count(11)
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.index'));

        $response
            ->assertOk()
            ->assertViewHas(
                'invoices',
                function (LengthAwarePaginator $invoices): bool {
                    return $invoices->count() === 10
                        && $invoices->total() === 11
                        && $invoices->perPage() === 10
                        && $invoices->currentPage() === 1;
                }
            );
    }
}
