<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
final class CustomerFactory extends Factory
{
    /**
     * Factoryが対応するモデルです。
     *
     * @var class-string<Customer>
     */
    protected $model = Customer::class;

    /**
     * 顧客の初期テストデータを定義します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->company(),
            'contact_name' => fake()->optional()->name(),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'postal_code' => fake()->optional()->postcode(),
            'address' => fake()->optional()->address(),
            'memo' => fake()->optional()->paragraph(),
            'business_card_front_path' => null,
            'business_card_back_path' => null,
        ];
    }
}