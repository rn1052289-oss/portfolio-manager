<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskDueNotificationHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TaskDueNotificationService
{
    /**
     * 指定した基準日に通知すべきタスクを取得します。
     *
     * @return Collection<int, array{
     *     task: Task,
     *     notification_type: string
     * }>
     */
    public function collectEligibleReminders(
        CarbonInterface|string|null $referenceDate = null
    ): Collection {
        $today = $this->resolveReferenceDate($referenceDate);

        return $this->candidateTaskQuery($today)
            ->get()
            ->map(function (Task $task) use ($today): ?array {
                $notificationType = $this->determineNotificationType(
                    $task,
                    $today
                );

                if ($notificationType === null) {
                    return null;
                }

                if ($this->hasBlockingHistory($task, $notificationType)) {
                    return null;
                }

                return [
                    'task' => $task,
                    'notification_type' => $notificationType,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * 通知対象を案件所有者ごとにまとめます。
     *
     * @param Collection<int, array{
     *     task: Task,
     *     notification_type: string
     * }> $reminders
     *
     * @return Collection<int, array{
     *     user: User,
     *     reminders: Collection<int, array{
     *         task: Task,
     *         notification_type: string
     *     }>
     * }>
     */
    public function groupByOwner(Collection $reminders): Collection
    {
        return $reminders
            ->groupBy(
                fn(array $reminder): int => $reminder['task']
                    ->project
                    ->user_id
            )
            ->map(function (Collection $ownerReminders): array {
                $firstReminder = $ownerReminders->first();

                return [
                    'user' => $firstReminder['task']->project->user,
                    'reminders' => $ownerReminders->values(),
                ];
            })
            ->values();
    }

    /**
     * 通知候補となるタスクの検索条件を作成します。
     */
    private function candidateTaskQuery(
        CarbonImmutable $today
    ): Builder {
        $notificationDates = [
            $today->toDateString(),
            $today->addDay()->toDateString(),
            $today->addDays(3)->toDateString(),
        ];

        return Task::query()
            ->with([
                'project:id,user_id,title',
                'project.user:id,name,email',
                'dueNotificationHistories:id,task_id,due_date,notification_type,status',
            ])
            ->whereNotNull('tasks.due_date')
            ->where(
                'tasks.status',
                '!=',
                Task::STATUS_COMPLETED
            )
            ->where(function (Builder $query) use ($today, $notificationDates): void {
                $query
                    ->whereDate(
                        'tasks.due_date',
                        '<',
                        $today->toDateString()
                    )
                    ->orWhereIn(
                        'tasks.due_date',
                        $notificationDates
                    );
            })
            ->whereHas(
                'project',
                function (Builder $projectQuery): void {
                    $projectQuery
                        ->whereColumn(
                            'projects.user_id',
                            'tasks.user_id'
                        )
                        ->whereHas(
                            'user',
                            function (Builder $userQuery): void {
                                $userQuery
                                    ->whereNotNull('email')
                                    ->where('email', '<>', '');
                            }
                        );
                }
            )
            ->orderBy('tasks.due_date')
            ->orderByRaw(
                '
                    CASE tasks.priority
                        WHEN ? THEN 1
                        WHEN ? THEN 2
                        WHEN ? THEN 3
                        ELSE 4
                    END
                ',
                [
                    Task::PRIORITY_HIGH,
                    Task::PRIORITY_MEDIUM,
                    Task::PRIORITY_LOW,
                ]
            )
            ->orderBy('tasks.sort_order')
            ->orderBy('tasks.created_at');
    }

    /**
     * タスクの期限から通知種別を判定します。
     */
    private function determineNotificationType(
        Task $task,
        CarbonImmutable $today
    ): ?string {
        if ($task->due_date === null) {
            return null;
        }

        $dueDate = CarbonImmutable::instance(
            $task->due_date
        )->startOfDay();

        return match (true) {
            $dueDate->lessThan($today)
            => TaskDueNotificationHistory::TYPE_OVERDUE,

            $dueDate->isSameDay($today)
            => TaskDueNotificationHistory::TYPE_DUE_TODAY,

            $dueDate->isSameDay($today->addDay())
            => TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE,

            $dueDate->isSameDay($today->addDays(3))
            => TaskDueNotificationHistory::TYPE_THREE_DAYS_BEFORE,

            default => null,
        };
    }

    /**
     * 同じ期限・同じ通知種別について、
     * 送信済みまたは送信処理中の履歴があるか確認します。
     */
    private function hasBlockingHistory(
        Task $task,
        string $notificationType
    ): bool {
        return $task->dueNotificationHistories
            ->contains(
                function (TaskDueNotificationHistory $history) use ($task, $notificationType): bool {
                    if (
                        $history->due_date === null
                        || $task->due_date === null
                    ) {
                        return false;
                    }

                    $isSameNotification = $history->due_date
                        ->isSameDay($task->due_date)
                        && $history->notification_type
                        === $notificationType;

                    $isBlockingStatus = in_array(
                        $history->status,
                        [
                            TaskDueNotificationHistory::STATUS_PENDING,
                            TaskDueNotificationHistory::STATUS_SENT,
                        ],
                        true
                    );

                    return $isSameNotification
                        && $isBlockingStatus;
                }
            );
    }

    /**
     * 通知判定に使用する基準日を日本時間で作成します。
     */
    private function resolveReferenceDate(
        CarbonInterface|string|null $referenceDate
    ): CarbonImmutable {
        $timezone = config(
            'app.timezone',
            'Asia/Tokyo'
        );

        if ($referenceDate instanceof CarbonInterface) {
            return CarbonImmutable::instance($referenceDate)
                ->setTimezone($timezone)
                ->startOfDay();
        }

        if (is_string($referenceDate)) {
            return CarbonImmutable::parse(
                $referenceDate,
                $timezone
            )->startOfDay();
        }

        return CarbonImmutable::now($timezone)
            ->startOfDay();
    }
}