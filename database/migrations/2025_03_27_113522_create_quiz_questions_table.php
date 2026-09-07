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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->foreign('quiz_category_id')->references('id')->on('quiz_categories')->onDelete('cascade');
            $table->text('question');
            $table->text('question_chinese')->nullable();
            $table->text('marks');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_category_id');
        });
        Schema::dropIfExists('quiz_questions');
    }
};
