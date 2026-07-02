<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 案件とタグを紐づける中間テーブルを作成する
     */
    public function up(): void
    {
        Schema::create('project_tag', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['project_id', 'tag_id']);
        });
    }

    /**
     * 中間テーブルを削除する
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tag');
    }
};