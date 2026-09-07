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
        Schema::table('user_attempt_quizzes', function (Blueprint $table) {
            $table->foreignId('quiz_id')
            ->nullable()
            
            ->constrained('quizzes')
            ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_attempt_quizzes', function (Blueprint $table) {
            $table->dropForeign(['quiz_id']); // Drop FK first
            $table->dropColumn('quiz_id');
        });
    }
};
