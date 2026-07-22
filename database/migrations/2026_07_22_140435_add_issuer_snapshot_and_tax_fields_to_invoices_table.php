<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 請求書へ発行者スナップショットと税情報を追加します。
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            /*
             * 請求書作成・更新時点の発行者情報です。
             *
             * BusinessProfileを後から変更しても、
             * 発行済み請求書の内容が変化しないように
             * Invoiceへコピーして保存します。
             *
             * 既存の請求書データとの互換性を保つため、
             * 今回はnullableで追加します。
             */
            $table->string('issuer_business_name')
                ->nullable();

            $table->string('issuer_representative_name')
                ->nullable();

            $table->string('issuer_email')
                ->nullable();

            $table->string('issuer_phone', 50)
                ->nullable();

            $table->string('issuer_postal_code', 20)
                ->nullable();

            $table->text('issuer_address')
                ->nullable();

            /*
             * 適格請求書発行事業者登録番号です。
             * 「T」+13桁を保存するため14文字にします。
             */
            $table->string('issuer_registration_number', 14)
                ->nullable();

            /*
             * 請求書発行時点の振込先情報です。
             */
            $table->string('issuer_bank_name')
                ->nullable();

            $table->string('issuer_bank_branch_name')
                ->nullable();

            $table->string('issuer_bank_account_type', 20)
                ->nullable();

            /*
             * Invoiceモデルでもencrypted castを設定する予定です。
             * 暗号化後の長い値を保存できるようtext型にします。
             */
            $table->text('issuer_bank_account_number')
                ->nullable();

            $table->string('issuer_bank_account_holder')
                ->nullable();

            /*
             * 実際の取引・役務提供日です。
             *
             * 既存の請求書には値がないためnullableにします。
             * 新規請求書では、当面issued_atと同じ日付を
             * Controller側から保存する予定です。
             */
            $table->date('transaction_date')
                ->nullable();

            /*
             * 今回は通常税率10％だけを扱います。
             * 将来、8％・0％などへ拡張できる整数型にします。
             */
            $table->unsignedTinyInteger('tax_rate')
                ->default(10);

            /*
             * 消費税の端数処理方法です。
             *
             * floor：切り捨て
             * round：四捨五入
             * ceil：切り上げ
             */
            $table->string('tax_rounding_method', 20)
                ->default('floor');
        });
    }

    /**
     * 追加した発行者スナップショットと税情報を削除します。
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn([
                'issuer_business_name',
                'issuer_representative_name',
                'issuer_email',
                'issuer_phone',
                'issuer_postal_code',
                'issuer_address',
                'issuer_registration_number',
                'issuer_bank_name',
                'issuer_bank_branch_name',
                'issuer_bank_account_type',
                'issuer_bank_account_number',
                'issuer_bank_account_holder',
                'transaction_date',
                'tax_rate',
                'tax_rounding_method',
            ]);
        });
    }
};
