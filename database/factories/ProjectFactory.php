<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Factoryが対応するModelです。
     *
     * @var class-string<Project>
     */
    protected $model = Project::class;

    /**
     * 案件の初期テストデータを定義します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'category_id' => null,
            'title' => fake()->words(3, true),
            'client_name' => fake()->company(),
            'description' => fake()->optional()->paragraph(),
            'cover_image_path' => null,
            'status' => 'not_started',
            'priority' => Project::PRIORITY_MEDIUM,
            'budget' => fake()->optional()->numberBetween(
                50_000,
                1_000_000
            ),
            'start_date' => $startDate->format('Y-m-d'),
            'deadline' => fake()
                ->dateTimeBetween($startDate, '+3 months')
                ->format('Y-m-d'),
        ];
    }
}
