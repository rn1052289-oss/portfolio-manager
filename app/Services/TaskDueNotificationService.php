<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskDueNotificationHistory;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TaskDueNotificationService
{
    /**
     * pending状態を処理中として扱う時間です。
     *
     * これを超えたpendingは、異常終了などで残ったものとして
     * 再取得できるようにします。
     */
    private const PENDING_TIMEOUT_MINUTES = 30;

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
     * 通知対象を抽出し、案件所有者へメールを送信します。
     *
     * @return array{
     *     reference_date: string,
     *     eligible_task_count: int,
     *     owner_group_count: int,
     *     claimed_task_count: int,
     *     concurrent_skip_count: int,
     *     sent_email_count: int,
     *     sent_task_count: int,
     *     failed_email_count: int,
     *     failed_task_count: int,
     *     failures: array<int, array{
     *         user_id: int,
     *         email: string,
     *         message: string
     *     }>
     * }
     */
    public function sendEligibleNotifications(
        CarbonInterface|string|null $referenceDate = null
    ): array {
        $today = $this->resolveReferenceDate($referenceDate);
        $reminders = $this->collectEligibleReminders($today);
        $ownerGroups = $this->groupByOwner($reminders);

        $summary = [
            'reference_date' => $today->toDateString(),
            'eligible_task_count' => $reminders->count(),
            'owner_group_count' => $ownerGroups->count(),
            'claimed_task_count' => 0,
            'concurrent_skip_count' => 0,
            'sent_email_count' => 0,
            'sent_task_count' => 0,
            'failed_email_count' => 0,
            'failed_task_count' => 0,
            'failures' => [],
        ];

        foreach ($ownerGroups as $ownerGroup) {
            /** @var User $user */
            $user = $ownerGroup['user'];

            /** @var Collection<int, array{
             *     task: Task,
             *     notification_type: string
             * }> $ownerReminders
             */
            $ownerReminders = $ownerGroup['reminders'];

            /** @var Collection<int, array{
             *     task: Task,
             *     notification_type: string
             * }> $claimedReminders
             */
            $claimedReminders = collect();

            /** @var Collection<int, TaskDueNotificationHistory> $histories */
            $histories = collect();

            foreach ($ownerReminders as $reminder) {
                $history = $this->claimReminderHistory(
                    $reminder['task'],
                    $user,
                    $reminder['notification_type']
                );

                if ($history === null) {
                    $summary['concurrent_skip_count']++;

                    continue;
                }

                $claimedReminders->push($reminder);
                $histories->push($history);
            }

            $claimedCount = $claimedReminders->count();

            $summary['claimed_task_count'] += $claimedCount;

            if ($claimedCount === 0) {
                continue;
            }

            try {
                $user->notify(
                    new TaskDueReminderNotification($claimedReminders)
                );

                $this->markHistoriesAsSent($histories);

                $summary['sent_email_count']++;
                $summary['sent_task_count'] += $claimedCount;
            } catch (Throwable $exception) {
                $errorMessage = Str::limit(
                    $exception->getMessage(),
                    5000,
                    ''
                );

                $this->markHistoriesAsFailed(
                    $histories,
                    $errorMessage
                );

                $summary['failed_email_count']++;
                $summary['failed_task_count'] += $claimedCount;
                $summary['failures'][] = [
                    'user_id' => $user->id,
                    'email' => (string) $user->email,
                    'message' => $errorMessage,
                ];

                Log::error(
                    'タスク期限通知メールの送信に失敗しました。',
                    [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'task_ids' => $claimedReminders
                            ->map(
                                fn(array $reminder): int => $reminder['task']->id
                            )
                            ->all(),
                        'exception' => $exception,
                    ]
                );
            }
        }

        return $summary;
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
                'dueNotificationHistories:id,task_id,due_date,notification_type,status,updated_at',
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

        $dueDate = CarbonImmutable::parse(
            $task->due_date->toDateString(),
            $today->getTimezone()
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
     * 送信を妨げる履歴があるか確認します。
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

                    if (!$isSameNotification) {
                        return false;
                    }

                    if (
                        $history->status
                        === TaskDueNotificationHistory::STATUS_SENT
                    ) {
                        return true;
                    }

                    if (
                        $history->status
                        === TaskDueNotificationHistory::STATUS_PENDING
                    ) {
                        return !$this->isStalePending($history);
                    }

                    return false;
                }
            );
    }

    /**
     * 送信対象の通知履歴をpending状態で確保します。
     *
     * 別処理が既に確保している場合や、
     * 送信済みの場合はnullを返します。
     */
    private function claimReminderHistory(
        Task $task,
        User $user,
        string $notificationType
    ): ?TaskDueNotificationHistory {
        if (
            $task->due_date === null
            || $user->email === null
            || $user->email === ''
        ) {
            return null;
        }

        $dueDate = $task->due_date->toDateString();

        return DB::transaction(
            function () use ($task, $user, $notificationType, $dueDate): ?TaskDueNotificationHistory {
                $history = $task
                    ->dueNotificationHistories()
                    ->createOrFirst(
                        [
                            'due_date' => $dueDate,
                            'notification_type' => $notificationType,
                        ],
                        [
                            'user_id' => $user->id,
                            'recipient_email' => $user->email,
                            'status' => TaskDueNotificationHistory::STATUS_PENDING,
                            'sent_at' => null,
                            'failed_at' => null,
                            'error_message' => null,
                        ]
                    );

                $wasRecentlyCreated = $history->wasRecentlyCreated;

                $lockedHistory = TaskDueNotificationHistory::query()
                    ->whereKey($history->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($wasRecentlyCreated) {
                    return $lockedHistory;
                }

                if (
                    $lockedHistory->status
                    === TaskDueNotificationHistory::STATUS_SENT
                ) {
                    return null;
                }

                if (
                    $lockedHistory->status
                    === TaskDueNotificationHistory::STATUS_PENDING
                    && !$this->isStalePending($lockedHistory)
                ) {
                    return null;
                }

                $lockedHistory->forceFill([
                    'user_id' => $user->id,
                    'recipient_email' => $user->email,
                    'status' => TaskDueNotificationHistory::STATUS_PENDING,
                    'sent_at' => null,
                    'failed_at' => null,
                    'error_message' => null,
                ])->save();

                return $lockedHistory->refresh();
            },
            3
        );
    }

    /**
     * pending状態が一定時間以上残っているか判定します。
     */
    private function isStalePending(
        TaskDueNotificationHistory $history
    ): bool {
        if ($history->updated_at === null) {
            return true;
        }

        $staleThreshold = CarbonImmutable::now(
            config('app.timezone', 'Asia/Tokyo')
        )->subMinutes(self::PENDING_TIMEOUT_MINUTES);

        return $history->updated_at->lessThan($staleThreshold);
    }

    /**
     * 通知履歴を送信成功へ更新します。
     *
     * @param Collection<int, TaskDueNotificationHistory> $histories
     */
    private function markHistoriesAsSent(
        Collection $histories
    ): void {
        if ($histories->isEmpty()) {
            return;
        }

        $now = CarbonImmutable::now(
            config('app.timezone', 'Asia/Tokyo')
        );

        TaskDueNotificationHistory::query()
            ->whereIn('id', $histories->pluck('id')->all())
            ->update([
                'status' => TaskDueNotificationHistory::STATUS_SENT,
                'sent_at' => $now,
                'failed_at' => null,
                'error_message' => null,
                'updated_at' => $now,
            ]);
    }

    /**
     * 通知履歴を送信失敗へ更新します。
     *
     * @param Collection<int, TaskDueNotificationHistory> $histories
     */
    private function markHistoriesAsFailed(
        Collection $histories,
        string $errorMessage
    ): void {
        if ($histories->isEmpty()) {
            return;
        }

        $now = CarbonImmutable::now(
            config('app.timezone', 'Asia/Tokyo')
        );

        TaskDueNotificationHistory::query()
            ->whereIn('id', $histories->pluck('id')->all())
            ->update([
                'status' => TaskDueNotificationHistory::STATUS_FAILED,
                'sent_at' => null,
                'failed_at' => $now,
                'error_message' => $errorMessage,
                'updated_at' => $now,
            ]);
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