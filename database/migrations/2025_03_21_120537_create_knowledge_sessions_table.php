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
        Schema::create('knowledge_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('category')->onDelete('cascade');
            $table->enum('session_type',['online','offline','hybrid'])->default('online');
            $table->string('age')->nullable();
            $table->string('link')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('venue')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('title_chinese')->nullable();
            $table->text('description_chinese')->nullable();
            $table->date('session_date')->nullable();
            $table->time('session_time')->nullable();
            $table->enum('status',['active','inactive'])->default('active');
            $table->enum('is_featured',['yes','no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::dropIfExists('knowledge_sessions');
    }
};
