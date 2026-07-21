<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * paymentsテーブルへ請求書IDを追加します。
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('invoice_id')
                ->nullable()
                ->after('project_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(
                ['invoice_id', 'created_at'],
                'payments_invoice_id_created_at_index'
            );
        });
    }

    /**
     * paymentsテーブルから請求書IDを削除します。
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_invoice_id_created_at_index');
            $table->dropConstrainedForeignId('invoice_id');
        });
    }
};