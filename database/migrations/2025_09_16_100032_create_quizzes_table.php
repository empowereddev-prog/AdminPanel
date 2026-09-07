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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->foreign('quiz_category_id')->references('id')->on('quiz_categories')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('age')->nullable();
            $table->string('image')->nullable();
            $table->string('color')->nullable();
            $table->string('title_color')->nullable();
            $table->enum('status',['active','inactive'])->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
