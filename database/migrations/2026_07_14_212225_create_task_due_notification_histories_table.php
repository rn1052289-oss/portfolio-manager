<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 通知履歴テーブルを作成します。
     */
    public function up(): void
    {
        Schema::create('task_due_notification_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('due_date');

            $table->string('notification_type', 50);

            $table->string('recipient_email');

            $table->string('status', 20)
                ->default('pending');

            $table->timestamp('sent_at')
                ->nullable();

            $table->timestamp('failed_at')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'task_id',
                    'due_date',
                    'notification_type',
                ],
                'task_due_notification_unique'
            );

            $table->index(
                [
                    'user_id',
                    'status',
                ],
                'task_due_notification_user_status_index'
            );
        });
    }

    /**
     * 通知履歴テーブルを削除します。
     */
    public function down(): void
    {
        Schema::dropIfExists('task_due_notification_histories');
    }
};