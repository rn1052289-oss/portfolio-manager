<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 事業者プロフィールテーブルを作成します。
     */
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->id();

            /*
             * 1ユーザーにつき1つの事業者プロフィールだけを
             * 所有できるようにunique制約を設定します。
             */
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            /*
             * 発行者の基本情報です。
             *
             * 既存ユーザーがプロフィール未登録でも動作できるよう、
             * 初期段階では各項目をnullableにします。
             * 必須項目は入力時とPDF生成時にアプリ側で確認します。
             */
            $table->string('business_name')
                ->nullable();

            $table->string('representative_name')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->string('phone', 50)
                ->nullable();

            $table->string('postal_code', 20)
                ->nullable();

            $table->text('address')
                ->nullable();

            /*
             * 「T」+13桁の適格請求書発行事業者登録番号を
             * 保存する想定です。
             */
            $table->string('invoice_registration_number', 14)
                ->nullable();

            /*
             * 請求書へ掲載する振込先情報です。
             */
            $table->string('bank_name')
                ->nullable();

            $table->string('bank_branch_name')
                ->nullable();

            $table->string('bank_account_type', 20)
                ->nullable();

            /*
             * 口座番号はModelのencrypted castで暗号化するため、
             * 暗号化後の長い文字列を保存できるtext型にします。
             */
            $table->text('bank_account_number')
                ->nullable();

            $table->string('bank_account_holder')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * 事業者プロフィールテーブルを削除します。
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
