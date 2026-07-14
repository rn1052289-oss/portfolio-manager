<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskDueNotificationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use LogicException;

class TaskDueReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param Collection<int, array{
     *     task: Task,
     *     notification_type: string
     * }> $reminders
     */
    public function __construct(
        public readonly Collection $reminders
    ) {
    }

    /**
     * 通知に使用するチャンネルを返します。
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * メール通知を作成します。
     */
    public function toMail(object $notifiable): MailMessage
    {
        $taskCount = $this->reminders->count();

        return (new MailMessage)
            ->subject(
                "【ClientWork Manager】期限を確認するタスクが{$taskCount}件あります"
            )
            ->view('mail.task-due-reminder', [
                'recipientName' => $notifiable->name ?: 'ご担当者',
                'taskCount' => $taskCount,
                'reminders' => $this->buildReminderViewData(),
                'dashboardUrl' => route('dashboard'),
            ]);
    }

    /**
     * 配列通知用のデータを返します。
     *
     * 今回はmailチャンネルだけを使用するため空配列です。
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    /**
     * メール本文へ渡すタスク情報を作成します。
     *
     * @return Collection<int, array{
     *     notification_type: string,
     *     notification_label: string,
     *     project_title: string,
     *     task_title: string,
     *     due_date: string,
     *     priority: string,
     *     priority_label: string,
     *     status: string,
     *     status_label: string,
     *     edit_url: string,
     *     task_list_url: string
     * }>
     */
    private function buildReminderViewData(): Collection
    {
        return $this->reminders
            ->map(function (array $reminder): array {
                $task = $reminder['task'];
                $notificationType = $reminder['notification_type'];
                $project = $task->project;

                if ($project === null) {
                    throw new LogicException(
                        "タスクID {$task->id} に案件が紐づいていません。"
                    );
                }

                return [
                    'notification_type' => $notificationType,
                    'notification_label' => $this->notificationTypeLabel(
                        $notificationType
                    ),
                    'project_title' => $project->title,
                    'task_title' => $task->title,
                    'due_date' => $task->due_date?->format('Y年m月d日')
                        ?? '未設定',
                    'priority' => $task->priority,
                    'priority_label' => $this->priorityLabel(
                        $task->priority
                    ),
                    'status' => $task->status,
                    'status_label' => $this->statusLabel(
                        $task->status
                    ),
                    'edit_url' => route(
                        'projects.tasks.edit',
                        [$project, $task]
                    ),
                    'task_list_url' => route(
                        'projects.tasks.index',
                        $project
                    ),
                ];
            })
            ->values();
    }

    /**
     * 通知種別を日本語へ変換します。
     */
    private function notificationTypeLabel(string $notificationType): string
    {
        return match ($notificationType) {
            TaskDueNotificationHistory::TYPE_THREE_DAYS_BEFORE => '期限3日前',
            TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE => '期限前日',
            TaskDueNotificationHistory::TYPE_DUE_TODAY => '期限当日',
            TaskDueNotificationHistory::TYPE_OVERDUE => '期限超過',
            default => '期限確認',
        };
    }

    /**
     * 優先度を日本語へ変換します。
     */
    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            Task::PRIORITY_HIGH => '高',
            Task::PRIORITY_MEDIUM => '中',
            Task::PRIORITY_LOW => '低',
            default => '未設定',
        };
    }

    /**
     * ステータスを日本語へ変換します。
     */
    private function statusLabel(string $status): string
    {
        return match ($status) {
            Task::STATUS_NOT_STARTED => '未着手',
            Task::STATUS_IN_PROGRESS => '進行中',
            Task::STATUS_COMPLETED => '完了',
            default => '不明',
        };
    }
}