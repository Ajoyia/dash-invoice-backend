<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('company_number')->nullable();
            $table->string('company_name');
            $table->string('company_id')->nullable();
            $table->string('vat_id')->nullable();
            $table->string('phone')->nullable();
            $table->enum('status', ['active', 'inactive', 'new'])->default('new')->nullable();
            $table->string('invoice_email')->nullable();
            $table->string('warning_invoice_email')->nullable();
            $table->string('notification_mail')->nullable();
            $table->string('contact_language')->nullable();
            $table->boolean('apply_reverse_charge')->default(0);
            $table->string('external_order_number')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->decimal('credits', 8, 2)->nullable();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->string('changed_by')->nullable();
            $table->integer('free_cases_count')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('deleted_at');
            $table->index('company_name');
            $table->index('company_number');
            $table->index('vat_id');
            $table->index('city');
            $table->index('country');
        });

        DB::table('companies')->whereNull('valid_from')->update([
            'valid_from' => '2025-01-01 00:00:00',
            'valid_to' => '9999-12-31 00:00:00',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
