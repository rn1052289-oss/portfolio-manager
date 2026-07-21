<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class InvoiceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_invoice_edit_page(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->get(
            route('invoices.edit', $invoice)
        );

        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_view_draft_invoice_edit_page(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '編集対象顧客',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create([
                'title' => '編集対象案件',
            ]);

        $invoice = Invoice::factory()
            ->for($user)
            ->for($project)
            ->for($customer)
            ->create([
                'invoice_number' => 'INV-202607-0001',
                'title' => '編集前タイトル',
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.edit', $invoice));

        $response
            ->assertOk()
            ->assertViewIs('invoices.form')
            ->assertViewHas('invoice', function (Invoice $viewInvoice) use ($invoice): bool {
                return $viewInvoice->is($invoice);
            })
            ->assertViewHasAll([
                'projects',
                'customers',
            ])
            ->assertSeeText('請求書を編集')
            ->assertSeeText('INV-202607-0001')
            ->assertSee('value="編集前タイトル"', false);
    }

    public function test_edit_page_only_displays_owned_projects_and_customers(): void
    {
        $user = User::factory()->create();

        $ownCustomer = Customer::factory()
            ->for($user)
            ->create();

        $ownProject = Project::factory()
            ->for($user)
            ->for($ownCustomer)
            ->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->for($ownProject)
            ->for($ownCustomer)
            ->create();

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create();

        $otherProject = Project::factory()
            ->for($otherUser)
            ->for($otherCustomer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.edit', $invoice));

        $response
            ->assertOk()
            ->assertViewHas(
                'projects',
                function (Collection $projects) use ($ownProject, $otherProject): bool {
                    return $projects->contains(
                        'id',
                        $ownProject->id
                    ) && ! $projects->contains(
                        'id',
                        $otherProject->id
                    );
                }
            )
            ->assertViewHas(
                'customers',
                function (Collection $customers) use ($ownCustomer, $otherCustomer): bool {
                    return $customers->contains(
                        'id',
                        $ownCustomer->id
                    ) && ! $customers->contains(
                        'id',
                        $otherCustomer->id
                    );
                }
            );
    }

    public function test_user_cannot_edit_another_users_invoice(): void
    {
        $owner = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($owner)
            ->create();

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->get(route('invoices.edit', $invoice));

        $response->assertForbidden();
    }

    public function test_sent_invoice_cannot_be_edited(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->sent()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('invoices.edit', $invoice));

        $response->assertForbidden();
    }
}
