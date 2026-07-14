<?php

namespace App\Console\Commands;

use App\Models\TaskDueNotificationHistory;
use App\Services\TaskDueNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class SendTaskDueNotifications extends Command
{
    /**
     * コマンド名とオプションです。
     *
     * @var string
     */
    protected $signature = 'tasks:send-due-notifications
        {--date= : 通知判定の基準日（Y-m-d）}
        {--dry-run : メール送信・通知履歴登録を行わず対象だけ確認する}';

    /**
     * コマンドの説明です。
     *
     * @var string
     */
    protected $description = '期限が近い未完了タスクを案件所有者へメール通知します';

    /**
     * コマンドを実行します。
     */
    public function handle(
        TaskDueNotificationService $service
    ): int {
        $referenceDate = $this->validateDateOption();

        if ($referenceDate === false) {
            return self::INVALID;
        }

        try {
            if ((bool) $this->option('dry-run')) {
                return $this->runDryRun(
                    $service,
                    $referenceDate
                );
            }

            return $this->runNotificationSend(
                $service,
                $referenceDate
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->newLine();
            $this->error('期限通知処理を完了できませんでした。');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * メールを送らず、通知対象だけを表示します。
     */
    private function runDryRun(
        TaskDueNotificationService $service,
        ?string $referenceDate
    ): int {
        $reminders = $service->collectEligibleReminders(
            $referenceDate
        );

        $ownerGroups = $service->groupByOwner($reminders);

        $resolvedDate = $this->resolvedReferenceDate(
            $referenceDate
        );

        $this->newLine();
        $this->info(
            'ドライランを実行しました。メール送信と履歴登録は行っていません。'
        );

        $this->line("基準日: {$resolvedDate}");
        $this->line("通知対象タスク数: {$reminders->count()}件");
        $this->line("通知対象ユーザー数: {$ownerGroups->count()}人");

        if ($reminders->isEmpty()) {
            $this->newLine();
            $this->info('通知対象のタスクはありません。');

            return self::SUCCESS;
        }

        $this->newLine();

        $this->table(
            [
                'タスクID',
                '案件名',
                'タスク名',
                '期限',
                '通知種別',
                '通知先',
            ],
            $this->dryRunRows($reminders)
        );

        return self::SUCCESS;
    }

    /**
     * 通知メールを実際に送信します。
     */
    private function runNotificationSend(
        TaskDueNotificationService $service,
        ?string $referenceDate
    ): int {
        $this->newLine();
        $this->info('タスク期限通知を開始します。');

        $summary = $service->sendEligibleNotifications(
            $referenceDate
        );

        $this->newLine();

        $this->table(
            ['項目', '結果'],
            [
                ['基準日', $summary['reference_date']],
                [
                    '通知候補タスク数',
                    "{$summary['eligible_task_count']}件",
                ],
                [
                    '通知対象ユーザー数',
                    "{$summary['owner_group_count']}人",
                ],
                [
                    '送信権を確保したタスク数',
                    "{$summary['claimed_task_count']}件",
                ],
                [
                    '同時実行などで除外した件数',
                    "{$summary['concurrent_skip_count']}件",
                ],
                [
                    '送信成功メール数',
                    "{$summary['sent_email_count']}通",
                ],
                [
                    '送信成功タスク数',
                    "{$summary['sent_task_count']}件",
                ],
                [
                    '送信失敗メール数',
                    "{$summary['failed_email_count']}通",
                ],
                [
                    '送信失敗タスク数',
                    "{$summary['failed_task_count']}件",
                ],
            ]
        );

        if ($summary['failed_email_count'] > 0) {
            $this->newLine();
            $this->error(
                '一部の通知メールを送信できませんでした。'
            );

            $this->table(
                [
                    'ユーザーID',
                    'メールアドレス',
                    'エラー',
                ],
                collect($summary['failures'])
                    ->map(
                        fn(array $failure): array => [
                            $failure['user_id'],
                            $failure['email'],
                            $failure['message'],
                        ]
                    )
                    ->all()
            );

            return self::FAILURE;
        }

        if ($summary['sent_email_count'] === 0) {
            $this->newLine();
            $this->info(
                '新しく送信する期限通知はありませんでした。'
            );

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(
            'タスク期限通知を正常に送信しました。'
        );

        return self::SUCCESS;
    }

    /**
     * --dateオプションを検証します。
     *
     * @return string|false|null
     */
    private function validateDateOption(): string|false|null
    {
        $dateOption = $this->option('date');

        if ($dateOption === null || $dateOption === '') {
            return null;
        }

        if (!is_string($dateOption)) {
            $this->error(
                '--dateにはY-m-d形式の日付を指定してください。'
            );

            return false;
        }

        $timezone = config(
            'app.timezone',
            'Asia/Tokyo'
        );

        try {
            $date = CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $dateOption,
                $timezone
            );
        } catch (Throwable) {
            $date = null;
        }

        if (
            $date === null
            || $date->format('Y-m-d') !== $dateOption
        ) {
            $this->error(
                '--dateには正しいY-m-d形式の日付を指定してください。'
            );

            $this->line(
                '例: --date=2026-07-20'
            );

            return false;
        }

        return $dateOption;
    }

    /**
     * 表示用の基準日を返します。
     */
    private function resolvedReferenceDate(
        ?string $referenceDate
    ): string {
        if ($referenceDate !== null) {
            return $referenceDate;
        }

        return CarbonImmutable::now(
            config('app.timezone', 'Asia/Tokyo')
        )->toDateString();
    }

    /**
     * ドライラン結果の表データを作成します。
     *
     * @param Collection<int, array{
     *     task: \App\Models\Task,
     *     notification_type: string
     * }> $reminders
     *
     * @return array<int, array<int, int|string>>
     */
    private function dryRunRows(
        Collection $reminders
    ): array {
        return $reminders
            ->map(function (array $reminder): array {
                $task = $reminder['task'];
                $project = $task->project;
                $owner = $project->user;

                return [
                    $task->id,
                    $project->title,
                    $task->title,
                    $task->due_date?->toDateString() ?? '未設定',
                    $this->notificationTypeLabel(
                        $reminder['notification_type']
                    ),
                    $owner->email,
                ];
            })
            ->all();
    }

    /**
     * 通知種別を表示用の日本語へ変換します。
     */
    private function notificationTypeLabel(
        string $notificationType
    ): string {
        return match ($notificationType) {
            TaskDueNotificationHistory::TYPE_THREE_DAYS_BEFORE
            => '期限3日前',

            TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE
            => '期限前日',

            TaskDueNotificationHistory::TYPE_DUE_TODAY
            => '期限当日',

            TaskDueNotificationHistory::TYPE_OVERDUE
            => '期限超過',

            default => $notificationType,
        };
    }
}