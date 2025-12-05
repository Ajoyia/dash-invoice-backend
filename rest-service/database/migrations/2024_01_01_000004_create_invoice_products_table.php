<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_products', function (Blueprint $table) {
            $table->string("id")->primary();
            $table->string('invoice_id')->references('id')->on('invoices')->onDelete('cascade')->nullable();
            $table->string('pos')->nullable();
            $table->string('article_number')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('quantity', 16, 2)->nullable();
            $table->decimal('tax', 16, 2)->nullable();
            $table->decimal('product_price', 16, 2)->nullable();
            $table->decimal('netto_total', 16, 2)->nullable();
            $table->decimal('credits', 10, 2)->nullable();
            $table->decimal('total_credits', 10, 2)->nullable();
            $table->decimal('credit_price', 10, 2)->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_products');
    }
};

