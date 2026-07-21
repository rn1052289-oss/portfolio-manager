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
        return $this->state(fn(): array => [
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
        return $this->state(fn(): array => [
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
        return $this->state(fn(): array => [
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
        return $this->state(fn(): array => [
            'status' => Invoice::STATUS_CANCELED,
            'canceled_at' => now(),
        ]);
    }
}