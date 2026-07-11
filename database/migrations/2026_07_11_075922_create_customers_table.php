<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 顧客管理用のcustomersテーブルを作成します。
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('address')->nullable();
            $table->text('memo')->nullable();

            $table->string('business_card_front_path')->nullable();
            $table->string('business_card_back_path')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'name'],
                'customers_user_id_name_unique'
            );
        });
    }

    /**
     * customersテーブルを削除します。
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};