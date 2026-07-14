<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDueNotificationHistory;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use App\Services\TaskDueNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskDueNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 通知対象だけを抽出し、対象外タスクを除外できることを確認します。
     */
    public function test_it_sends_only_eligible_task_due_notifications(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        $overdueTask = Task::factory()
            ->forProject($project)
            ->inProgress()
            ->highPriority()
            ->dueOn('2030-01-09')
            ->create([
                'title' => '期限超過タスク',
            ]);

        $todayTask = Task::factory()
            ->forProject($project)
            ->notStarted()
            ->highPriority()
            ->dueOn('2030-01-10')
            ->create([
                'title' => '期限当日タスク',
            ]);

        $tomorrowTask = Task::factory()
            ->forProject($project)
            ->inProgress()
            ->mediumPriority()
            ->dueOn('2030-01-11')
            ->create([
                'title' => '期限前日タスク',
            ]);

        $threeDaysTask = Task::factory()
            ->forProject($project)
            ->notStarted()
            ->lowPriority()
            ->dueOn('2030-01-13')
            ->create([
                'title' => '期限3日前タスク',
            ]);

        $twoDaysTask = Task::factory()
            ->forProject($project)
            ->notStarted()
            ->dueOn('2030-01-12')
            ->create([
                'title' => '2日後の対象外タスク',
            ]);

        $completedTask = Task::factory()
            ->forProject($project)
            ->completed()
            ->dueOn('2030-01-10')
            ->create([
                'title' => '完了済み対象外タスク',
            ]);

        $withoutDueDateTask = Task::factory()
            ->forProject($project)
            ->notStarted()
            ->withoutDueDate()
            ->create([
                'title' => '期限なし対象外タスク',
            ]);

        $mismatchedOwnerTask = Task::factory()
            ->forProject($project)
            ->notStarted()
            ->dueOn('2030-01-10')
            ->create([
                'user_id' => $otherUser->id,
                'title' => '所有者不一致対象外タスク',
            ]);

        $summary = app(TaskDueNotificationService::class)
            ->sendEligibleNotifications('2030-01-10');

        $this->assertSame(4, $summary['eligible_task_count']);
        $this->assertSame(1, $summary['owner_group_count']);
        $this->assertSame(4, $summary['claimed_task_count']);
        $this->assertSame(1, $summary['sent_email_count']);
        $this->assertSame(4, $summary['sent_task_count']);
        $this->assertSame(0, $summary['failed_email_count']);

        $notifications = Notification::sent(
            $owner,
            TaskDueReminderNotification::class
        );

        $this->assertCount(1, $notifications);

        /** @var TaskDueReminderNotification $notification */
        $notification = $notifications->first();

        $notifiedTaskIds = $notification->reminders
            ->map(
                fn(array $reminder): int => $reminder['task']->id
            )
            ->all();

        $notificationTypes = $notification->reminders
            ->pluck('notification_type')
            ->all();

        $this->assertEqualsCanonicalizing(
            [
                $overdueTask->id,
                $todayTask->id,
                $tomorrowTask->id,
                $threeDaysTask->id,
            ],
            $notifiedTaskIds
        );

        $this->assertEqualsCanonicalizing(
            [
                TaskDueNotificationHistory::TYPE_OVERDUE,
                TaskDueNotificationHistory::TYPE_DUE_TODAY,
                TaskDueNotificationHistory::TYPE_ONE_DAY_BEFORE,
                TaskDueNotificationHistory::TYPE_THREE_DAYS_BEFORE,
            ],
            $notificationTypes
        );

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            4
        );

        $this->assertDatabaseMissing(
            'task_due_notification_histories',
            ['task_id' => $twoDaysTask->id]
        );

        $this->assertDatabaseMissing(
            'task_due_notification_histories',
            ['task_id' => $completedTask->id]
        );

        $this->assertDatabaseMissing(
            'task_due_notification_histories',
            ['task_id' => $withoutDueDateTask->id]
        );

        $this->assertDatabaseMissing(
            'task_due_notification_histories',
            ['task_id' => $mismatchedOwnerTask->id]
        );
    }

    /**
     * 他ユーザーのタスクが同じメールへ混ざらないことを確認します。
     */
    public function test_it_groups_notifications_by_project_owner(): void
    {
        Notification::fake();

        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $projectA = Project::factory()
            ->for($ownerA)
            ->create();

        $projectB = Project::factory()
            ->for($ownerB)
            ->create();

        $taskA1 = Task::factory()
            ->forProject($projectA)
            ->dueOn('2030-02-10')
            ->create();

        $taskA2 = Task::factory()
            ->forProject($projectA)
            ->dueOn('2030-02-11')
            ->create();

        $taskB = Task::factory()
            ->forProject($projectB)
            ->dueOn('2030-02-10')
            ->create();

        $summary = app(TaskDueNotificationService::class)
            ->sendEligibleNotifications('2030-02-10');

        $this->assertSame(3, $summary['eligible_task_count']);
        $this->assertSame(2, $summary['owner_group_count']);
        $this->assertSame(2, $summary['sent_email_count']);

        $ownerANotifications = Notification::sent(
            $ownerA,
            TaskDueReminderNotification::class
        );

        $ownerBNotifications = Notification::sent(
            $ownerB,
            TaskDueReminderNotification::class
        );

        $this->assertCount(1, $ownerANotifications);
        $this->assertCount(1, $ownerBNotifications);

        /** @var TaskDueReminderNotification $ownerANotification */
        $ownerANotification = $ownerANotifications->first();

        /** @var TaskDueReminderNotification $ownerBNotification */
        $ownerBNotification = $ownerBNotifications->first();

        $this->assertEqualsCanonicalizing(
            [$taskA1->id, $taskA2->id],
            $ownerANotification->reminders
                ->map(
                    fn(array $reminder): int => $reminder['task']->id
                )
                ->all()
        );

        $this->assertSame(
            [$taskB->id],
            $ownerBNotification->reminders
                ->map(
                    fn(array $reminder): int => $reminder['task']->id
                )
                ->all()
        );

        $this->assertTrue(
            $ownerANotification->reminders->every(
                fn(array $reminder): bool =>
                $reminder['task']->project->user_id === $ownerA->id
            )
        );

        $this->assertTrue(
            $ownerBNotification->reminders->every(
                fn(array $reminder): bool =>
                $reminder['task']->project->user_id === $ownerB->id
            )
        );
    }

    /**
     * 同じ期限・同じ通知種別を重複送信しないことを確認します。
     */
    public function test_it_does_not_send_the_same_notification_twice(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        $task = Task::factory()
            ->forProject($project)
            ->dueOn('2030-03-10')
            ->create();

        $service = app(TaskDueNotificationService::class);

        $firstSummary = $service
            ->sendEligibleNotifications('2030-03-10');

        $secondSummary = $service
            ->sendEligibleNotifications('2030-03-10');

        $this->assertSame(1, $firstSummary['sent_email_count']);
        $this->assertSame(1, $firstSummary['sent_task_count']);

        $this->assertSame(0, $secondSummary['eligible_task_count']);
        $this->assertSame(0, $secondSummary['sent_email_count']);
        $this->assertSame(0, $secondSummary['sent_task_count']);

        $this->assertCount(
            1,
            Notification::sent(
                $owner,
                TaskDueReminderNotification::class
            )
        );

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            1
        );

        $this->assertDatabaseHas(
            'task_due_notification_histories',
            [
                'task_id' => $task->id,
                'due_date' => '2030-03-10',
                'notification_type' =>
                    TaskDueNotificationHistory::TYPE_DUE_TODAY,
                'status' =>
                    TaskDueNotificationHistory::STATUS_SENT,
            ]
        );
    }

    /**
     * 期限を変更した場合は、新しい期限として通知できることを確認します。
     */
    public function test_it_can_send_again_after_the_due_date_changes(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        $task = Task::factory()
            ->forProject($project)
            ->dueOn('2030-04-10')
            ->create();

        $service = app(TaskDueNotificationService::class);

        $firstSummary = $service
            ->sendEligibleNotifications('2030-04-10');

        $task->update([
            'due_date' => '2030-04-11',
        ]);

        $secondSummary = $service
            ->sendEligibleNotifications('2030-04-11');

        $this->assertSame(1, $firstSummary['sent_email_count']);
        $this->assertSame(1, $secondSummary['sent_email_count']);

        $this->assertCount(
            2,
            Notification::sent(
                $owner,
                TaskDueReminderNotification::class
            )
        );

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            2
        );

        $this->assertDatabaseHas(
            'task_due_notification_histories',
            [
                'task_id' => $task->id,
                'due_date' => '2030-04-10',
                'notification_type' =>
                    TaskDueNotificationHistory::TYPE_DUE_TODAY,
                'status' =>
                    TaskDueNotificationHistory::STATUS_SENT,
            ]
        );

        $this->assertDatabaseHas(
            'task_due_notification_histories',
            [
                'task_id' => $task->id,
                'due_date' => '2030-04-11',
                'notification_type' =>
                    TaskDueNotificationHistory::TYPE_DUE_TODAY,
                'status' =>
                    TaskDueNotificationHistory::STATUS_SENT,
            ]
        );
    }

    /**
     * failed履歴は再試行し、成功後にsentへ更新できることを確認します。
     */
    public function test_it_retries_a_failed_notification_history(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        $task = Task::factory()
            ->forProject($project)
            ->dueOn('2030-05-10')
            ->create();

        $history = $task->dueNotificationHistories()->create([
            'user_id' => $owner->id,
            'due_date' => '2030-05-10',
            'notification_type' =>
                TaskDueNotificationHistory::TYPE_DUE_TODAY,
            'recipient_email' => $owner->email,
            'status' => TaskDueNotificationHistory::STATUS_FAILED,
            'sent_at' => null,
            'failed_at' => now(),
            'error_message' => '一時的なSMTPエラー',
        ]);

        $summary = app(TaskDueNotificationService::class)
            ->sendEligibleNotifications('2030-05-10');

        $this->assertSame(1, $summary['eligible_task_count']);
        $this->assertSame(1, $summary['claimed_task_count']);
        $this->assertSame(1, $summary['sent_email_count']);
        $this->assertSame(0, $summary['failed_email_count']);

        $this->assertCount(
            1,
            Notification::sent(
                $owner,
                TaskDueReminderNotification::class
            )
        );

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            1
        );

        $history->refresh();

        $this->assertSame(
            TaskDueNotificationHistory::STATUS_SENT,
            $history->status
        );

        $this->assertNotNull($history->sent_at);
        $this->assertNull($history->failed_at);
        $this->assertNull($history->error_message);
    }
}