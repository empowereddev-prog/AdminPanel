<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The same drift as the users table, on the tables the mood, quiz, watch
 * history and mail features depend on. Each of these columns is queried or
 * written by live code, and every one of them currently produces
 * "Unknown column" at runtime:
 *
 *   child_moods.date                        MoodTrackerController::storeChildMood
 *   moods.type                              negative-mood detection
 *   user_content_watch_histories.is_completed  watch progress + point awards
 *   user_attempt_quizzes.user_id            every quiz attempt lookup
 *   email_templates.language                ___mail_sender / sendContactEmail
 *   faqs.type                               FaqController::index (school/parent/child)
 *
 * Guarded so it is safe to re-run against a database patched by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Written as Y-m-d and compared with whereDate/>= ranges.
        if (Schema::hasTable('child_moods') && !Schema::hasColumn('child_moods', 'date')) {
            Schema::table('child_moods', function (Blueprint $table) {
                $table->date('date')->nullable()->after('points');
                // Indexed alone, not as (child_id, date): child_id's own index
                // backs a foreign key, and a composite leading with it becomes
                // the FK's index, which then cannot be dropped on rollback.
                $table->index('date');
            });

            // Existing rows predate the column; fall back to the day they were
            // recorded so history and streak queries stay meaningful.
            DB::statement("UPDATE `child_moods` SET `date` = DATE(`created_at`) WHERE `date` IS NULL");
        }

        // Mood::where('type', 'negative') / 'positive'. Left nullable on
        // purpose: guessing a default would silently mis-classify existing
        // moods, and the negative-mood streak logic keys off this.
        if (Schema::hasTable('moods') && !Schema::hasColumn('moods', 'type')) {
            Schema::table('moods', function (Blueprint $table) {
                $table->enum('type', ['positive', 'negative'])->nullable()->after('name_chinese');
            });
        }

        if (Schema::hasTable('user_content_watch_histories')
            && !Schema::hasColumn('user_content_watch_histories', 'is_completed')) {
            Schema::table('user_content_watch_histories', function (Blueprint $table) {
                $table->enum('is_completed', ['yes', 'no'])->default('no')->after('total_video_duration');
            });
        }

        if (Schema::hasTable('user_attempt_quizzes')
            && !Schema::hasColumn('user_attempt_quizzes', 'user_id')) {
            Schema::table('user_attempt_quizzes', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

        // FaqController::index filters on school/parent/child. Without this the
        // whole endpoint fell into its catch and returned "Something went
        // wrong" with the SQL error, as a 200.
        if (Schema::hasTable('faqs') && !Schema::hasColumn('faqs', 'type')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->enum('type', ['school', 'parent', 'child'])->nullable()->after('answer');
                $table->index(['type', 'status']);
            });
        }

        if (Schema::hasTable('email_templates') && !Schema::hasColumn('email_templates', 'language')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->string('language', 20)->default('english')->after('variable_name');
                $table->index(['variable_name', 'language']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('child_moods') && Schema::hasColumn('child_moods', 'date')) {
            Schema::table('child_moods', function (Blueprint $table) {
                $table->dropIndex(['date']);
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('moods') && Schema::hasColumn('moods', 'type')) {
            Schema::table('moods', fn (Blueprint $table) => $table->dropColumn('type'));
        }

        if (Schema::hasTable('user_content_watch_histories')
            && Schema::hasColumn('user_content_watch_histories', 'is_completed')) {
            Schema::table('user_content_watch_histories',
                fn (Blueprint $table) => $table->dropColumn('is_completed'));
        }

        if (Schema::hasTable('user_attempt_quizzes')
            && Schema::hasColumn('user_attempt_quizzes', 'user_id')) {
            Schema::table('user_attempt_quizzes', function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('faqs') && Schema::hasColumn('faqs', 'type')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropIndex(['type', 'status']);
                $table->dropColumn('type');
            });
        }

        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_templates', 'language')) {
            Schema::table('email_templates', function (Blueprint $table) {
                $table->dropIndex(['variable_name', 'language']);
                $table->dropColumn('language');
            });
        }
    }
};
