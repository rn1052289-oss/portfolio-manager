<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class InvoiceRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * FormRequestをHTTP経由で検証するため、
     * テスト専用の一時ルートを登録します。
     */
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post(
            '/test/invoices',
            function (StoreInvoiceRequest $request) {
                return response()->json([
                    'validated' => $request->validated(),
                ]);
            }
        );

        Route::middleware('web')->put(
            '/test/invoices/{invoice}',
            function (UpdateInvoiceRequest $request, Invoice $invoice) {
                return response()->json([
                    'invoice_id' => $invoice->id,
                    'validated' => $request->validated(),
                ]);
            }
        );
    }

    /**
     * 正しい請求書入力はバリデーションを通過します。
     */
    public function test_valid_invoice_store_request_passes_validation(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->for($user)->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'validated.project_id',
                $project->id
            )
            ->assertJsonPath(
                'validated.customer_id',
                $customer->id
            )
            ->assertJsonPath(
                'validated.subtotal',
                100000
            )
            ->assertJsonPath(
                'validated.tax_amount',
                10000
            )
            ->assertJsonPath(
                'validated.total_amount',
                110000
            );
    }

    /**
     * 他ユーザーが所有する案件は請求書に指定できません。
     */
    public function test_store_request_rejects_another_users_project(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
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
            ->postJson('/test/invoices', [
                'project_id' => $otherProject->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'project_id',
            ]);
    }

    /**
     * 他ユーザーが所有する顧客は請求書に指定できません。
     */
    public function test_store_request_rejects_another_users_customer(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $otherCustomer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_id',
            ]);
    }

    /**
     * 案件に紐づく顧客と異なる顧客は請求書に指定できません。
     */
    public function test_store_request_rejects_customer_that_does_not_match_project(): void
    {
        $user = User::factory()->create();

        $projectCustomer = Customer::factory()
            ->for($user)
            ->create();

        $differentCustomer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($projectCustomer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $differentCustomer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'customer_id',
            ]);
    }

    /**
     * 小計と消費税の合計に一致しない合計金額は拒否されます。
     */
    public function test_store_request_rejects_inconsistent_total_amount(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 120000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'total_amount',
            ]);
    }

    /**
     * 発行日より前の支払期限は拒否されます。
     */
    public function test_store_request_rejects_due_date_before_issued_at(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費',
                'description' => 'コーポレートサイト制作一式',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-07-20',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'due_date',
            ]);
    }

    /**
     * サーバー管理項目の改ざんは拒否されます。
     */
    public function test_store_request_rejects_prohibited_fields(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/test/invoices', [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',

                // 改ざん対象
                'user_id' => 999,
                'status' => 'paid',
                'invoice_number' => 'INV-999999',
                'public_token' => 'fake-token',
                'billing_name' => '改ざん',
                'paid_at' => now()->toDateTimeString(),
                'sent_at' => now()->toDateTimeString(),
                'pdf_path' => '/tmp/test.pdf',
                'send_count' => 999,
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'status',
                'invoice_number',
                'public_token',
                'billing_name',
                'paid_at',
                'sent_at',
                'pdf_path',
                'send_count',
            ]);
    }

    /**
     * 所有者は下書き状態の請求書を更新できます。
     */
    public function test_owner_can_update_draft_invoice(): void
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

        $response = $this
            ->actingAs($user)
            ->putJson("/test/invoices/{$invoice->id}", [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => 'Webサイト制作費（修正版）',
                'description' => '追加対応を含む制作費',
                'subtotal' => 150000,
                'tax_amount' => 15000,
                'total_amount' => 165000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('invoice_id', $invoice->id)
            ->assertJsonPath(
                'validated.title',
                'Webサイト制作費（修正版）'
            )
            ->assertJsonPath(
                'validated.subtotal',
                150000
            )
            ->assertJsonPath(
                'validated.tax_amount',
                15000
            )
            ->assertJsonPath(
                'validated.total_amount',
                165000
            );
    }

    /**
     * 送信済みの請求書は更新できません。
     */
    public function test_owner_cannot_update_sent_invoice(): void
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
                'status' => Invoice::STATUS_SENT,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/test/invoices/{$invoice->id}", [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => '送信済み請求書の改ざん',
                'description' => '更新してはいけない内容',
                'subtotal' => 150000,
                'tax_amount' => 15000,
                'total_amount' => 165000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response->assertForbidden();
    }

    /**
     * 支払済みの請求書は更新できません。
     */
    public function test_owner_cannot_update_paid_invoice(): void
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
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/test/invoices/{$invoice->id}", [
                'project_id' => $project->id,
                'customer_id' => $customer->id,
                'title' => '支払済み請求書の改ざん',
                'description' => '更新してはいけない内容',
                'subtotal' => 150000,
                'tax_amount' => 15000,
                'total_amount' => 165000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response->assertForbidden();
    }

    /**
     * 他ユーザーの請求書は更新できません。
     */
    public function test_user_cannot_update_another_users_invoice(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create();

        $otherProject = Project::factory()
            ->for($otherUser)
            ->for($otherCustomer)
            ->create();

        $invoice = Invoice::factory()
            ->for($otherUser)
            ->for($otherProject)
            ->for($otherCustomer)
            ->create([
                'status' => Invoice::STATUS_DRAFT,
            ]);

        $response = $this
            ->actingAs($user)
            ->putJson("/test/invoices/{$invoice->id}", [
                'project_id' => $otherProject->id,
                'customer_id' => $otherCustomer->id,
                'title' => '不正更新',
                'description' => '他人の請求書',
                'subtotal' => 100000,
                'tax_amount' => 10000,
                'total_amount' => 110000,
                'issued_at' => '2026-07-21',
                'due_date' => '2026-08-31',
            ]);

        $response->assertForbidden();
    }
}
