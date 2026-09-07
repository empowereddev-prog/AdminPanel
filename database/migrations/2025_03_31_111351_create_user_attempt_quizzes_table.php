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
        Schema::create('user_attempt_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->foreign('quiz_category_id')
              ->references('id')->on('quiz_categories')->onDelete('cascade');
            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')
            ->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->unsignedBigInteger('selected_option_id');
            $table->foreign('selected_option_id')
            ->references('id')->on('quiz_question_options')->onDelete('cascade');
            $table->integer('marks_obtained')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_category_id');
            $table->dropConstrainedForeignId('question_id');
            $table->dropConstrainedForeignId('selected_option_id');
        });
        Schema::dropIfExists('user_attempt_quizzes');
    }
};
