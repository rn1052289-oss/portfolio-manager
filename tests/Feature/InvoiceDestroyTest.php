<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InvoiceDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは請求書を削除できません。
     */
    public function test_guest_cannot_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create();

        $response = $this->delete(
            route('invoices.destroy', $invoice)
        );

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * 所有者は決済履歴のない下書きを削除できます。
     */
    public function test_owner_can_delete_draft_invoice_without_payments(): void
    {
        $user = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($user)
            ->create([
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('invoices.destroy', $invoice));

        $response
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas(
                'success',
                '請求書を削除しました。'
            );

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * 他ユーザーの請求書は削除できません。
     */
    public function test_user_cannot_delete_another_users_invoice(): void
    {
        $owner = User::factory()->create();

        $invoice = Invoice::factory()
            ->for($owner)
            ->create([
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->delete(route('invoices.destroy', $invoice));

        $response->assertForbidden();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);
    }

    /**
     * 下書き以外の請求書は削除できません。
     */
    public function test_non_draft_invoices_cannot_be_deleted(): void
    {
        $user = User::factory()->create();

        $statuses = [
            Invoice::STATUS_SENT,
            Invoice::STATUS_PAID,
            Invoice::STATUS_OVERDUE,
            Invoice::STATUS_CANCELED,
        ];

        foreach ($statuses as $status) {
            $invoice = Invoice::factory()
                ->for($user)
                ->create([
                    'status' => $status,
                ]);

            $response = $this
                ->actingAs($user)
                ->delete(route('invoices.destroy', $invoice));

            $response->assertForbidden();

            $this->assertDatabaseHas('invoices', [
                'id' => $invoice->id,
                'status' => $status,
            ]);
        }
    }

    /**
     * 決済履歴が紐づく下書きは削除できません。
     */
    public function test_draft_invoice_with_payment_cannot_be_deleted(): void
    {
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
            ->create([
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $payment = Payment::query()->forceCreate([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'invoice_id' => $invoice->id,

            'stripe_payment_intent_id' => 'pi_invoice_delete_test_001',
            'idempotency_key' => 'invoice-delete-001',

            'amount' => $invoice->total_amount,
            'currency' => 'jpy',
            'status' => Payment::STATUS_REQUIRES_PAYMENT_METHOD,

            'description' => '請求書削除制御テスト用決済',
            'failure_message' => null,

            'paid_at' => null,
            'canceled_at' => null,
            'metadata' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('invoices.destroy', $invoice));

        $response
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas(
                'error',
                '決済履歴が紐づいている請求書は削除できません。'
            );

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);
    }

    /**
     * 存在しない請求書IDは404になります。
     */
    public function test_missing_invoice_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/invoices/999999');

        $response->assertNotFound();
    }
}
