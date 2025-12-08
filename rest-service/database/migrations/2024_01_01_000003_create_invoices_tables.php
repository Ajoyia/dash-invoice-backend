<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('invoice_number')->nullable();
            $table->string('company_id')->references('id')->on('companies')->onDelete('cascade')->nullable();
            $table->string('reference_invoice_id')->references('id')->on('invoices')->onDelete('cascade')->nullable();
            $table->enum('invoice_type', ['invoice-correction', 'invoice', 'invoice-storno'])->default('invoice')->nullable();
            $table->enum('status', ['draft', 'approved', 'sent', 'warning level 1', 'warning level 2', 'warning level 3', 'paid'])->default('draft')->nullable();
            $table->date('due_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('external_order_number')->nullable();
            $table->longText('custom_notes_fields')->nullable();
            $table->string('user_id')->nullable();
            $table->boolean('apply_reverse_charge')->default(0)->nullable();
            $table->decimal('netto', 16, 2)->nullable();
            $table->decimal('tax_amount', 16, 2)->nullable();
            $table->decimal('total_amount', 16, 2)->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->index('invoice_type');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('due_date');
            $table->index('invoice_date');
            $table->index(['company_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['company_id', 'created_at']);
            $table->index(['invoice_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
