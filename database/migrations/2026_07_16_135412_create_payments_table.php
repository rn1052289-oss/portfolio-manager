<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 決済履歴テーブルを作成する。
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('stripe_payment_intent_id')
                ->unique();

            $table->uuid('idempotency_key')
                ->unique();

            $table->unsignedBigInteger('amount');

            $table->char('currency', 3)
                ->default('jpy');

            $table->string('status', 50)
                ->index();

            $table->text('description')
                ->nullable();

            $table->text('failure_message')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('canceled_at')
                ->nullable();

            $table->json('metadata')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'created_at',
            ]);

            $table->index([
                'project_id',
                'created_at',
            ]);
        });
    }

    /**
     * 決済履歴テーブルを削除する。
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
