<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * 請求書テーブルを作成します。
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('invoice_number', 50);

            $table->uuid('public_token')
                ->unique();

            $table->string('title');

            $table->text('description')
                ->nullable();

            $table->unsignedBigInteger('subtotal');

            $table->unsignedBigInteger('tax_amount')
                ->default(0);

            $table->unsignedBigInteger('total_amount');

            $table->char('currency', 3)
                ->default('jpy');

            $table->string('billing_name');

            $table->string('billing_contact_name')
                ->nullable();

            $table->string('billing_email')
                ->nullable();

            $table->string('billing_phone', 50)
                ->nullable();

            $table->string('billing_postal_code', 20)
                ->nullable();

            $table->string('billing_address')
                ->nullable();

            $table->string('status', 50)
                ->default('draft');

            $table->date('issued_at');

            $table->date('due_date');

            $table->timestamp('sent_at')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->timestamp('canceled_at')
                ->nullable();

            $table->string('pdf_path')
                ->nullable();

            $table->string('last_sent_to')
                ->nullable();

            $table->unsignedInteger('send_count')
                ->default(0);

            $table->timestamps();

            $table->unique(
                ['user_id', 'invoice_number'],
                'invoices_user_id_invoice_number_unique'
            );

            $table->index(
                ['user_id', 'status'],
                'invoices_user_id_status_index'
            );

            $table->index(
                ['project_id', 'created_at'],
                'invoices_project_id_created_at_index'
            );

            $table->index(
                ['customer_id', 'created_at'],
                'invoices_customer_id_created_at_index'
            );

            $table->index(
                ['status', 'due_date'],
                'invoices_status_due_date_index'
            );
        });
    }

    /**
     * 請求書テーブルを削除します。
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};