<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Factoryが対応するModelです。
     *
     * @var class-string<Task>
     */
    protected $model = Task::class;

    /**
     * Factoryの追加設定です。
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Task $task): void {
            if (
                $task->user_id !== null
                || $task->project_id === null
            ) {
                return;
            }

            $userId = Project::query()
                ->whereKey($task->project_id)
                ->value('user_id');

            if ($userId === null) {
                throw new LogicException(
                    "案件ID {$task->project_id} の所有者を取得できません。"
                );
            }

            $task->user_id = (int) $userId;
        });
    }

    /**
     * タスクの初期テストデータを定義します。
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = fake()->boolean(80)
            ? fake()
                ->dateTimeBetween('today', '+1 month')
                ->format('Y-m-d')
            : null;

        return [
            'user_id' => null,
            'project_id' => Project::factory(),
            'title' => fake()->sentence(4),
            'memo' => fake()->optional()->paragraph(),
            'status' => Task::STATUS_NOT_STARTED,
            'priority' => Task::PRIORITY_MEDIUM,
            'due_date' => $dueDate,
            'completed_at' => null,
            'sort_order' => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * 指定案件と同じ所有者のタスクを作成します。
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn(): array => [
            'project_id' => $project->id,
            'user_id' => $project->user_id,
        ]);
    }

    /**
     * 未着手状態にします。
     */
    public function notStarted(): static
    {
        return $this->state(fn(): array => [
            'status' => Task::STATUS_NOT_STARTED,
            'completed_at' => null,
        ]);
    }

    /**
     * 進行中状態にします。
     */
    public function inProgress(): static
    {
        return $this->state(fn(): array => [
            'status' => Task::STATUS_IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    /**
     * 完了状態にします。
     */
    public function completed(): static
    {
        return $this->state(fn(): array => [
            'status' => Task::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * 優先度を低にします。
     */
    public function lowPriority(): static
    {
        return $this->state(fn(): array => [
            'priority' => Task::PRIORITY_LOW,
        ]);
    }

    /**
     * 優先度を中にします。
     */
    public function mediumPriority(): static
    {
        return $this->state(fn(): array => [
            'priority' => Task::PRIORITY_MEDIUM,
        ]);
    }

    /**
     * 優先度を高にします。
     */
    public function highPriority(): static
    {
        return $this->state(fn(): array => [
            'priority' => Task::PRIORITY_HIGH,
        ]);
    }

    /**
     * 指定した期限を設定します。
     */
    public function dueOn(
        CarbonInterface|string $dueDate
    ): static {
        $resolvedDueDate = $dueDate instanceof CarbonInterface
            ? $dueDate->toDateString()
            : CarbonImmutable::parse(
                $dueDate,
                config('app.timezone', 'Asia/Tokyo')
            )->toDateString();

        return $this->state(fn(): array => [
            'due_date' => $resolvedDueDate,
        ]);
    }

    /**
     * 期限なしにします。
     */
    public function withoutDueDate(): static
    {
        return $this->state(fn(): array => [
            'due_date' => null,
        ]);
    }
}