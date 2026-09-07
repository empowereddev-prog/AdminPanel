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
        Schema::create('video_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('video_link')->nullable();
            $table->string('last_watched_duration')->nullable();
            $table->string('video_duration')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('status',['active','inactive'])->default('active');
            $table->unsignedBigInteger('points')->default(0);
            $table->enum('is_featured',['yes','no'])->default('no');
            $table->string('title_chinese')->nullable();
            $table->text('description_chinese')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('video_contents');
    }
};
