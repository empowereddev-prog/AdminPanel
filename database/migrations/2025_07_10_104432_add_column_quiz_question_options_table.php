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
        Schema::table('quiz_question_options', function (Blueprint $table) {
            $table->longText('description')->nullable();
            $table->string('color')->nullable();
            $table->string('title_color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question_options', function (Blueprint $table) {
            $table->dropColumn(['color', 'title_color','description']);
        });
    }
};
