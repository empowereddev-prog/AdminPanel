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
        Schema::create('avatar_images', function (Blueprint $table) {
            $table->id();
            $table->string('body_part')->nullable();
            $table->string('preview_image')->nullable(); // Image shown in UI
            $table->string('apply_image')->nullable(); // Image applied to avatar
            $table->unsignedBigInteger('points')->default(0); // Image applied to avatar
            $table->enum('status',['active','inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avatar_images');
    }
};
