<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求書番号の連番管理テーブルを作成します。
     */
    public function up(): void
    {
        Schema::create(
            'invoice_number_sequences',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * 請求書の発行年月です。
                 *
                 * 例：
                 * 202607
                 */
                $table->char('period', 6);

                /*
                 * このユーザー・発行年月で、
                 * 最後に使用した連番です。
                 */
                $table->unsignedInteger('last_number')
                    ->default(0);

                $table->timestamps();

                /*
                 * 同じユーザー・発行年月の管理行を
                 * 複数作成できないようにします。
                 */
                $table->unique(
                    [
                        'user_id',
                        'period',
                    ],
                    'invoice_number_sequences_user_period_unique'
                );
            }
        );
    }

    /**
     * 請求書番号の連番管理テーブルを削除します。
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'invoice_number_sequences'
        );
    }
};
