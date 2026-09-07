<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->foreignId('quiz_id')
                ->nullable()
                
                ->constrained('quizzes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']); // Drop FK first
            $table->dropColumn('quiz_id');
        });
    }
};
