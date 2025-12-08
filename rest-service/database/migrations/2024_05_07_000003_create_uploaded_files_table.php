<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->string('fileable_id')->nullable();
            $table->string('fileable_type')->nullable();
            $table->string('type')->nullable();
            $table->string('storage_name')->nullable();
            $table->string('storage_size')->nullable();
            $table->string('viewable_name')->nullable();
            $table->timestamps();

            $table->index(['fileable_id', 'fileable_type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
