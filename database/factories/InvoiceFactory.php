<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    /**
     * 対象モデルです。
     *
     * @var class-string<Invoice>
     */
    protected $model = Invoice::class;

    /**
     * 請求書の初期データを定義します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10_000, 500_000);
        $taxAmount = (int) floor($subtotal * 0.1);

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'customer_id' => Customer::factory(),

            'invoice_number' => sprintf(
                'INV-%s-%04d',
                now()->format('Ym'),
                fake()->unique()->numberBetween(1, 9999)
            ),

            'public_token' => (string) Str::uuid(),

            'title' => fake()->randomElement([
                'Webサイト制作費',
                'システム開発費',
                '保守運用費',
                '追加作業費',
            ]),

            'description' => fake()->optional()->sentence(),

            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
            'currency' => 'jpy',

            'billing_name' => fake()->company(),
            'billing_contact_name' => fake()->name(),
            'billing_email' => fake()->companyEmail(),
            'billing_phone' => fake()->phoneNumber(),
            'billing_postal_code' => fake()->postcode(),
            'billing_address' => fake()->address(),

            /*
             * 請求書発行時点の発行者情報です。
             */
            'issuer_business_name' => 'ClientWork Studio',
            'issuer_representative_name' => '山田 太郎',
            'issuer_email' => 'billing@example.com',
            'issuer_phone' => '03-1234-5678',
            'issuer_postal_code' => '100-0001',
            'issuer_address' => '東京都千代田区千代田1-1',
            'issuer_registration_number' => 'T1234567890123',

            /*
             * 請求書発行時点の振込先情報です。
             *
             * 口座番号はInvoiceモデルのencrypted castによって
             * DB保存時に暗号化されます。
             */
            'issuer_bank_name' => 'テスト銀行',
            'issuer_bank_branch_name' => '中央支店',
            'issuer_bank_account_type' => '普通',
            'issuer_bank_account_number' => '1234567',
            'issuer_bank_account_holder' => 'ヤマダ タロウ',

            'transaction_date' => today(),
            'tax_rate' => Invoice::DEFAULT_TAX_RATE,
            'tax_rounding_method' => Invoice::TAX_ROUNDING_FLOOR,

            'status' => Invoice::STATUS_DRAFT,

            'issued_at' => today(),
            'due_date' => today()->addMonth(),

            'sent_at' => null,
            'paid_at' => null,
            'canceled_at' => null,

            'pdf_path' => null,
            'last_sent_to' => null,
            'send_count' => 0,
        ];
    }

    /**
     * 送信済み請求書の状態です。
     */
    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
            'last_sent_to' => fake()->safeEmail(),
            'send_count' => 1,
        ]);
    }

    /**
     * 入金済み請求書の状態です。
     */
    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => Invoice::STATUS_PAID,
            'sent_at' => now()->subDays(7),
            'paid_at' => now(),
            'last_sent_to' => fake()->safeEmail(),
            'send_count' => 1,
        ]);
    }

    /**
     * 支払期限切れ請求書の状態です。
     */
    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => Invoice::STATUS_OVERDUE,
            'issued_at' => today()->subMonth(),
            'due_date' => today()->subDay(),
            'sent_at' => now()->subMonth(),
            'last_sent_to' => fake()->safeEmail(),
            'send_count' => 1,
        ]);
    }

    /**
     * キャンセル済み請求書の状態です。
     */
    public function canceled(): static
    {
        return $this->state(fn (): array => [
            'status' => Invoice::STATUS_CANCELED,
            'canceled_at' => now(),
        ]);
    }
}
