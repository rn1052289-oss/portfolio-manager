<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDueNotificationHistory;
use App\Models\User;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tests\TestCase;

class SendTaskDueNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ドライランでは通知送信と履歴登録を行わないことを確認します。
     */
    public function test_dry_run_does_not_send_notifications_or_create_histories(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        Task::factory()
            ->forProject($project)
            ->notStarted()
            ->highPriority()
            ->dueOn('2030-06-10')
            ->create([
                'title' => 'ドライラン確認タスク',
            ]);

        $this->artisan(
            'tasks:send-due-notifications',
            [
                '--date' => '2030-06-10',
                '--dry-run' => true,
            ]
        )
            ->expectsOutputToContain(
                'ドライランを実行しました。'
            )
            ->expectsOutputToContain(
                '基準日: 2030-06-10'
            )
            ->expectsOutputToContain(
                '通知対象タスク数: 1件'
            )
            ->expectsOutputToContain(
                '通知対象ユーザー数: 1人'
            )
            ->expectsOutputToContain(
                'ドライラン確認タスク'
            )
            ->assertSuccessful();

        Notification::assertNothingSent();

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            0
        );
    }

    /**
     * 存在しない日付を拒否することを確認します。
     */
    public function test_it_rejects_an_invalid_date_option(): void
    {
        Notification::fake();

        $this->artisan(
            'tasks:send-due-notifications',
            [
                '--date' => '2030-02-30',
                '--dry-run' => true,
            ]
        )
            ->expectsOutputToContain(
                '--dateには正しいY-m-d形式の日付を指定してください。'
            )
            ->expectsOutputToContain(
                '例: --date=2026-07-20'
            )
            ->assertExitCode(
                SymfonyCommand::INVALID
            );

        Notification::assertNothingSent();

        $this->assertDatabaseCount(
            'task_due_notification_histories',
            0
        );
    }

    /**
     * 通常実行で通知し、同じ通知を重複送信しないことを確認します。
     */
    public function test_it_sends_notifications_and_prevents_duplicate_sends(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $project = Project::factory()
            ->for($owner)
            ->create();

        $task = Task::factory()
            ->forProject($project)
            ->inProgress()
            ->highPriority()
            ->dueOn('2030-07-10')
            ->create([
                'title' => 'Command送信確認タスク',
            ]);

        $this->artisan(
            'tasks:send-due-notifications',
            [
                '--date' => '2030-07-10',
            ]
        )
            ->expectsOutputToContain(
                'タスク期限通知を開始します。'
            )
            ->expectsOutputToContain(
                'タスク期限通知を正常に送信しました。'
            )
            ->assertSuccessful();

        $sentNotifications = Notification::sent(
            $owner,
            TaskDueReminderNotification::class
        );

        $this->assertCount(1, $sentNotifications);

        /** @var TaskDueReminderNotification $notification */
        $notification = $sentNotifications->first();

        $this->assertCount(
            1,
            $notification->reminders
        );

        $this->assertSame(
            $task->id,
            $notification->reminders
                ->first()['task']
                ->id
        );

        $this->assertSame(
            TaskDueNotificationHistory::TYPE_DUE_TODAY,
            $notification->reminders
                ->first()['notification_type']
        );

        $this->assertDatabaseHas(
            'task_due_notification_histories',
            [
                'task_id' => $task->id,
                'user_id' => $owner->id,
                'due_date' => '2030-07-10',
                'notification_type' =>
                    TaskDueNotificationHistory::TYPE_DUE_TODAY,
                'recipient_email' => $owner->email,
                'status' =>
                    TaskDueNotificationHistory::STATUS_SENT,
            ]
        );

        $this->artisan(
            'tasks:send-due-notifications',
            [
                '--date' => '2030-07-10',
            ]
        )
            ->expectsOutputToContain(
                '新しく送信する期限通知はありませんでした。'
            )
            ->assertSuccessful();

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
    }
}