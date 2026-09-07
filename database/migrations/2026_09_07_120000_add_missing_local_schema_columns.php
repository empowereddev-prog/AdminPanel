<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'is_notification')) {
                $table->string('is_notification')->default('true');
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('moods', function (Blueprint $table) {
            if (!Schema::hasColumn('moods', 'comment_english')) {
                $table->longText('comment_english')->nullable();
            }
            if (!Schema::hasColumn('moods', 'comment_chinese')) {
                $table->longText('comment_chinese')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['parent_id', 'is_notification', 'remember_token'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('moods', function (Blueprint $table) {
            foreach (['comment_english', 'comment_chinese'] as $column) {
                if (Schema::hasColumn('moods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
