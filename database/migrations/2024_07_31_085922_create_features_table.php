<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->nullable();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('icon')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('language', ['english', 'simplified_chinese','traditional_chinese'])->default('english');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
