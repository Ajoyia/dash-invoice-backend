<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_template_assignments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('module');
            $table->string('mail_template_id')->nullable();
            $table->string('cc')->nullable();
            $table->string('bcc')->nullable();
            $table->string('sender_mail')->nullable();
            $table->integer('reminder_hours')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('module');
            $table->index('mail_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_assignments');
    }
};

