<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class InvoiceStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未ログインユーザーは請求書を保存できません。
     */
    public function test_guest_cannot_store_invoice(): void
    {
        $response = $this->post(
            route('invoices.store'),
            []
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseCount(
            'invoices',
            0
        );

        $this->assertDatabaseCount(
            'invoice_number_sequences',
            0
        );
    }

    /**
     * 正しい入力で請求書を下書き保存できます。
     */
    public function test_authenticated_user_can_store_draft_invoice(): void
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
            ->post(
                route('invoices.store'),
                $this->validPayload(
                    $project,
                    $customer
                )
            );

        $response
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas(
                'success',
                '請求書を下書きとして保存しました。'
            );

        $invoice = Invoice::query()
            ->sole();

        $this->assertSame(
            $user->id,
            $invoice->user_id
        );

        $this->assertSame(
            $project->id,
            $invoice->project_id
        );

        $this->assertSame(
            $customer->id,
            $invoice->customer_id
        );

        $this->assertSame(
            'Webサイト制作費',
            $invoice->title
        );

        $this->assertSame(
            'コーポレートサイト制作一式',
            $invoice->description
        );

        $this->assertSame(
            Invoice::STATUS_DRAFT,
            $invoice->status
        );

        $this->assertSame(
            'jpy',
            $invoice->currency
        );

        $this->assertSame(
            '2026-07-21',
            $invoice->getRawOriginal('issued_at')
        );

        $this->assertSame(
            '2026-08-21',
            $invoice->getRawOriginal('due_date')
        );
    }

    /**
     * 税額と合計金額はブラウザから送られた値を信用せず、
     * 小計からサーバー側で再計算します。
     */
    public function test_store_recalculates_tax_and_total_on_server(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $payload = $this->validPayload(
            $project,
            $customer
        );

        /*
         * FormRequest上は、
         * 100001 + 1 = 100002で整合しています。
         *
         * しかし、Controllerでは税率10％で
         * 再計算される必要があります。
         */
        $payload['subtotal'] = 100001;
        $payload['tax_amount'] = 1;
        $payload['total_amount'] = 100002;

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.store'),
                $payload
            );

        $response->assertRedirect(
            route('invoices.index')
        );

        $invoice = Invoice::query()
            ->sole();

        $this->assertSame(
            100001,
            $invoice->subtotal
        );

        $this->assertSame(
            10000,
            $invoice->tax_amount
        );

        $this->assertSame(
            110001,
            $invoice->total_amount
        );
    }

    /**
     * 顧客情報を請求書作成時点の内容として保存し、
     * 公開用トークンなどのシステム項目を生成します。
     */
    public function test_store_snapshots_customer_information_and_generates_system_fields(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create([
                'name' => '株式会社旧顧客名',
                'contact_name' => '請求 太郎',
                'email' => 'billing@example.com',
                'phone' => '03-1234-5678',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1',
            ]);

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.store'),
                $this->validPayload(
                    $project,
                    $customer
                )
            );

        $response->assertRedirect(
            route('invoices.index')
        );

        /*
         * 請求書作成後に顧客マスタを変更します。
         */
        $customer->update([
            'name' => '株式会社新顧客名',
            'contact_name' => '変更後担当者',
            'email' => 'changed@example.com',
            'phone' => '06-9999-9999',
            'postal_code' => '530-0001',
            'address' => '大阪府大阪市北区梅田1-1',
        ]);

        $invoice = Invoice::query()
            ->sole();

        $this->assertSame(
            '株式会社旧顧客名',
            $invoice->billing_name
        );

        $this->assertSame(
            '請求 太郎',
            $invoice->billing_contact_name
        );

        $this->assertSame(
            'billing@example.com',
            $invoice->billing_email
        );

        $this->assertSame(
            '03-1234-5678',
            $invoice->billing_phone
        );

        $this->assertSame(
            '100-0001',
            $invoice->billing_postal_code
        );

        $this->assertSame(
            '東京都千代田区千代田1-1',
            $invoice->billing_address
        );

        $this->assertTrue(
            Str::isUuid($invoice->public_token)
        );

        $this->assertNull(
            $invoice->sent_at
        );

        $this->assertNull(
            $invoice->paid_at
        );

        $this->assertNull(
            $invoice->canceled_at
        );

        $this->assertNull(
            $invoice->pdf_path
        );

        $this->assertNull(
            $invoice->last_sent_to
        );

        $this->assertSame(
            0,
            $invoice->send_count
        );
    }

    /**
     * 事業者プロフィールを請求書作成時点の発行者情報として保存し、
     * 税情報と取引日もシステム側で設定します。
     */
    public function test_store_snapshots_business_profile_and_tax_information(): void
    {
        $user = User::factory()->create();

        $businessProfile = $user
            ->businessProfile()
            ->create([
                'business_name' => 'スナップショット前の屋号',
                'representative_name' => '請求 山田',
                'email' => 'issuer@example.com',
                'phone' => '03-1111-2222',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1',
                'invoice_registration_number' => 'T1234567890123',
                'bank_name' => 'スナップショット銀行',
                'bank_branch_name' => '中央支店',
                'bank_account_type' => '普通',
                'bank_account_number' => '1234567',
                'bank_account_holder' => 'セイキュウ ヤマダ',
            ]);

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(
                route('invoices.store'),
                $this->validPayload(
                    $project,
                    $customer
                )
            );

        $response
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHasNoErrors();

        /*
         * 請求書作成後に事業者プロフィールを変更します。
         *
         * Invoiceへ保存したスナップショットが、
         * 後から変化しないことを確認します。
         */
        $businessProfile->update([
            'business_name' => '変更後の屋号',
            'bank_name' => '変更後銀行',
            'bank_account_number' => '7654321',
        ]);

        $invoice = Invoice::query()
            ->sole();

        $this->assertSame(
            'スナップショット前の屋号',
            $invoice->issuer_business_name
        );

        $this->assertSame(
            '請求 山田',
            $invoice->issuer_representative_name
        );

        $this->assertSame(
            'issuer@example.com',
            $invoice->issuer_email
        );

        $this->assertSame(
            '03-1111-2222',
            $invoice->issuer_phone
        );

        $this->assertSame(
            '100-0001',
            $invoice->issuer_postal_code
        );

        $this->assertSame(
            '東京都千代田区千代田1-1',
            $invoice->issuer_address
        );

        $this->assertSame(
            'T1234567890123',
            $invoice->issuer_registration_number
        );

        $this->assertSame(
            'スナップショット銀行',
            $invoice->issuer_bank_name
        );

        $this->assertSame(
            '中央支店',
            $invoice->issuer_bank_branch_name
        );

        $this->assertSame(
            '普通',
            $invoice->issuer_bank_account_type
        );

        $this->assertSame(
            '1234567',
            $invoice->issuer_bank_account_number
        );

        $this->assertSame(
            'セイキュウ ヤマダ',
            $invoice->issuer_bank_account_holder
        );

        /*
         * 現在の仕様では、取引日は発行日と同じ日付です。
         */
        $this->assertSame(
            '2026-07-21',
            $invoice->getRawOriginal('transaction_date')
        );

        $this->assertSame(
            Invoice::DEFAULT_TAX_RATE,
            $invoice->tax_rate
        );

        $this->assertSame(
            Invoice::TAX_ROUNDING_FLOOR,
            $invoice->tax_rounding_method
        );

        /*
         * Model経由では復号された口座番号を取得できますが、
         * DBの生値は平文と異なる暗号文である必要があります。
         */
        $rawAccountNumber = $invoice->getRawOriginal(
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
    }

    /**
     * 発行者スナップショットや税設定などのシステム項目は、
     * リクエストから直接指定できません。
     */
    public function test_store_rejects_system_managed_issuer_and_tax_fields(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

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
                $customer
            ),
            $systemManagedFields
        );

        $response = $this
            ->actingAs($user)
            ->from(route('invoices.create'))
            ->post(
                route('invoices.store'),
                $payload
            );

        $response
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors(
                array_keys($systemManagedFields)
            );

        $this->assertDatabaseCount(
            'invoices',
            0
        );

        /*
         * バリデーションで拒否されたため、
         * 請求書番号の連番も消費されません。
         */
        $this->assertDatabaseCount(
            'invoice_number_sequences',
            0
        );
    }

    /**
     * 同じユーザー・同じ発行月では、
     * 請求書番号が0001から順番に発行されます。
     */
    public function test_store_generates_sequential_invoice_numbers(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()
            ->for($user)
            ->create();

        $project = Project::factory()
            ->for($user)
            ->for($customer)
            ->create();

        $firstPayload = $this->validPayload(
            $project,
            $customer
        );

        $firstPayload['title'] = '1件目の請求書';

        $secondPayload = $this->validPayload(
            $project,
            $customer
        );

        $secondPayload['title'] = '2件目の請求書';

        $this
            ->actingAs($user)
            ->post(
                route('invoices.store'),
                $firstPayload
            )
            ->assertRedirect(route('invoices.index'));

        $this
            ->actingAs($user)
            ->post(
                route('invoices.store'),
                $secondPayload
            )
            ->assertRedirect(route('invoices.index'));

        $invoiceNumbers = Invoice::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->pluck('invoice_number')
            ->all();

        $this->assertSame(
            [
                'INV-202607-0001',
                'INV-202607-0002',
            ],
            $invoiceNumbers
        );

        $this->assertDatabaseHas(
            'invoice_number_sequences',
            [
                'user_id' => $user->id,
                'period' => '202607',
                'last_number' => 2,
            ]
        );
    }

    /**
     * 他ユーザーが所有する案件は使用できません。
     */
    public function test_store_rejects_another_users_project(): void
    {
        $user = User::factory()->create();

        $ownCustomer = Customer::factory()
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

        $payload = $this->validPayload(
            $otherProject,
            $ownCustomer
        );

        $response = $this
            ->actingAs($user)
            ->from(route('invoices.create'))
            ->post(
                route('invoices.store'),
                $payload
            );

        $response
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors([
                'project_id',
            ]);

        $this->assertDatabaseCount(
            'invoices',
            0
        );

        $this->assertDatabaseCount(
            'invoice_number_sequences',
            0
        );
    }

    /**
     * 他ユーザーが所有する顧客は使用できません。
     */
    public function test_store_rejects_another_users_customer(): void
    {
        $user = User::factory()->create();

        $ownCustomer = Customer::factory()
            ->for($user)
            ->create();

        $ownProject = Project::factory()
            ->for($user)
            ->for($ownCustomer)
            ->create();

        $otherUser = User::factory()->create();

        $otherCustomer = Customer::factory()
            ->for($otherUser)
            ->create();

        $payload = $this->validPayload(
            $ownProject,
            $otherCustomer
        );

        $response = $this
            ->actingAs($user)
            ->from(route('invoices.create'))
            ->post(
                route('invoices.store'),
                $payload
            );

        $response
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors([
                'customer_id',
            ]);

        $this->assertDatabaseCount(
            'invoices',
            0
        );

        $this->assertDatabaseCount(
            'invoice_number_sequences',
            0
        );
    }

    /**
     * 正常な請求書保存データです。
     *
     * @return array<string, int|string>
     */
    private function validPayload(
        Project $project,
        Customer $customer
    ): array {
        return [
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'title' => 'Webサイト制作費',
            'description' => 'コーポレートサイト制作一式',
            'subtotal' => 100000,
            'tax_amount' => 10000,
            'total_amount' => 110000,
            'issued_at' => '2026-07-21',
            'due_date' => '2026-08-21',
        ];
    }
}
