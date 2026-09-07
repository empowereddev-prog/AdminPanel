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
        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_category_id');
            $table->foreign('quiz_category_id')
              ->references('id')->on('quiz_categories')->onDelete('cascade');
            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')
              ->references('id')->on('quiz_questions')->onDelete('cascade');
            $table->text('option_text');
            $table->text('option_text_chinese')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->softDeletes();
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
        });
        Schema::dropIfExists('quiz_question_options');
    }
};
