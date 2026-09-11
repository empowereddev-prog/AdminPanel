<?php

use Database\Seeders\AdminOtpEmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single additive schema/data alignment for this repo.
 *
 * Replaces the split 2026-09-07 / 09-10 / 09-11 migrations. Every schema
 * change is guarded so it is safe on a database that already ran any of those
 * files, or that was patched by hand in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->alignUsersCoreColumns();
        $this->alignUsersApplicationColumns();
        $this->alignMoods();
        $this->alignDeeplinkContent();
        $this->alignSupportingTables();
        $this->seedAdminOtpTemplate();
    }

    public function down(): void
    {
        if (Schema::hasTable('child_moods') && Schema::hasColumn('child_moods', 'date')) {
            Schema::table('child_moods', function (Blueprint $table) {
                $table->dropIndex(['date']);
                $table->dropColumn('date');
            });
        }

        if (Schema::hasTable('moods')) {
            Schema::table('moods', function (Blueprint $table) {
                foreach (['comment_english', 'comment_chinese', 'type'] as $column) {
                    if (Schema::hasColumn('moods', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('user_content_watch_histories')
            && Schema::hasColumn('user_content_watch_histories', 'is_completed')) {
            Schema::table('user_content_watch_histories', fn (Blueprint $table) => $table->dropColumn('is_completed'));
        }

        if (Schema::hasTable('user_attempt_quizzes') && Schema::hasColumn('user_attempt_quizzes', 'user_id')) {
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

        if (Schema::hasTable('knowledge_sessions') && Schema::hasColumn('knowledge_sessions', 'canonical_url')) {
            Schema::table('knowledge_sessions', fn (Blueprint $table) => $table->dropColumn('canonical_url'));
        }

        if (Schema::hasTable('video_contents') && Schema::hasColumn('video_contents', 'canonical_url')) {
            Schema::table('video_contents', fn (Blueprint $table) => $table->dropColumn('canonical_url'));
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'parent_id', 'is_notification', 'remember_token',
                'is_mood_updated', 'is_avatar_primary', 'is_first_login',
                'avtar_image', 'loyalty_points', 'dob',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }
        });

        if ($this->enumHasValue('users', 'is_mobile_verified', 'yes')) {
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` VARCHAR(10) NOT NULL DEFAULT '0'");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = '1' WHERE `is_mobile_verified` = 'yes'");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = '0' WHERE `is_mobile_verified` <> '1'");
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` ENUM('0','1') NOT NULL DEFAULT '0'");
        }

        if (Schema::hasColumn('users', 'user_type')) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `user_type` "
                . "ENUM('user','admin','parent','teacher') NULL DEFAULT NULL"
            );
        }
    }

    private function alignUsersCoreColumns(): void
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
    }

    private function alignUsersApplicationColumns(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('users', 'dob')) {
                $table->string('dob', 7)->nullable()->after('gender');
            }
            if (!Schema::hasColumn('users', 'loyalty_points')) {
                $table->decimal('loyalty_points', 10, 2)->default(0)->after('battery_points');
            }
            if (!Schema::hasColumn('users', 'avtar_image')) {
                $table->string('avtar_image')->nullable()->after('profile_image');
            }
            if (!Schema::hasColumn('users', 'is_first_login')) {
                $table->enum('is_first_login', ['yes', 'no'])->default('yes')->after('status');
            }
            if (!Schema::hasColumn('users', 'is_avatar_primary')) {
                $table->enum('is_avatar_primary', ['yes', 'no'])->default('no')->after('avtar_image');
            }
            if (!Schema::hasColumn('users', 'is_mood_updated')) {
                $table->enum('is_mood_updated', ['yes', 'no'])->default('no')->after('is_first_login');
            }
        });

        if (Schema::hasColumn('users', 'user_type') && !$this->enumHasValue('users', 'user_type', 'child')) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `user_type` "
                . "ENUM('user','admin','parent','teacher','child') NULL DEFAULT NULL"
            );
        }

        if (Schema::hasColumn('users', 'is_mobile_verified') && !$this->enumHasValue('users', 'is_mobile_verified', 'yes')) {
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` VARCHAR(10) NOT NULL DEFAULT 'no'");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = 'yes' WHERE `is_mobile_verified` IN ('1', 'yes')");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = 'no'  WHERE `is_mobile_verified` NOT IN ('yes')");
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` ENUM('yes','no') NOT NULL DEFAULT 'no'");
        }
    }

    private function alignMoods(): void
    {
        if (!Schema::hasTable('moods')) {
            return;
        }

        Schema::table('moods', function (Blueprint $table) {
            if (!Schema::hasColumn('moods', 'comment_english')) {
                $table->longText('comment_english')->nullable();
            }
            if (!Schema::hasColumn('moods', 'comment_chinese')) {
                $table->longText('comment_chinese')->nullable();
            }
            if (!Schema::hasColumn('moods', 'type')) {
                $table->enum('type', ['positive', 'negative'])->nullable()->after('name_chinese');
            }
        });
    }

    private function alignDeeplinkContent(): void
    {
        if (Schema::hasTable('knowledge_sessions')) {
            Schema::table('knowledge_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('knowledge_sessions', 'user_type')) {
                    $table->string('user_type')->nullable();
                }
                if (!Schema::hasColumn('knowledge_sessions', 'canonical_url')) {
                    $table->string('canonical_url')->nullable();
                }
            });
        }

        if (Schema::hasTable('video_contents') && !Schema::hasColumn('video_contents', 'canonical_url')) {
            Schema::table('video_contents', function (Blueprint $table) {
                $table->string('canonical_url')->nullable();
            });
        }

        $base = rtrim((string) env('DEEPLINK_PUBLIC_BASE_URL', env('APP_URL', 'https://admin.empoweredhealth.asia')), '/');
        if ($base === '') {
            return;
        }

        if (Schema::hasTable('knowledge_sessions') && Schema::hasColumn('knowledge_sessions', 'canonical_url')) {
            DB::table('knowledge_sessions')->orderBy('id')->chunkById(200, function ($rows) use ($base) {
                foreach ($rows as $row) {
                    $url = $base . '/d/article/' . $row->id;
                    if (($row->canonical_url ?? null) !== $url) {
                        DB::table('knowledge_sessions')->where('id', $row->id)->update(['canonical_url' => $url]);
                    }
                }
            });
        }

        if (Schema::hasTable('video_contents') && Schema::hasColumn('video_contents', 'canonical_url')) {
            DB::table('video_contents')->orderBy('id')->chunkById(200, function ($rows) use ($base) {
                foreach ($rows as $row) {
                    $url = $base . '/d/podcast/' . $row->id;
                    if (($row->canonical_url ?? null) !== $url) {
                        DB::table('video_contents')->where('id', $row->id)->update(['canonical_url' => $url]);
                    }
                }
            });
        }
    }

    private function alignSupportingTables(): void
    {
        if (Schema::hasTable('child_moods') && !Schema::hasColumn('child_moods', 'date')) {
            Schema::table('child_moods', function (Blueprint $table) {
                $table->date('date')->nullable()->after('points');
                $table->index('date');
            });
            DB::statement("UPDATE `child_moods` SET `date` = DATE(`created_at`) WHERE `date` IS NULL");
        }

        if (Schema::hasTable('user_content_watch_histories')
            && !Schema::hasColumn('user_content_watch_histories', 'is_completed')) {
            Schema::table('user_content_watch_histories', function (Blueprint $table) {
                $table->enum('is_completed', ['yes', 'no'])->default('no')->after('total_video_duration');
            });
        }

        if (Schema::hasTable('user_attempt_quizzes') && !Schema::hasColumn('user_attempt_quizzes', 'user_id')) {
            Schema::table('user_attempt_quizzes', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            });
        }

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

    private function seedAdminOtpTemplate(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        (new AdminOtpEmailTemplateSeeder())->run();
    }

    private function enumHasValue(string $table, string $column, string $value): bool
    {
        $definition = DB::selectOne(
            "SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $definition && str_contains($definition->type, "'{$value}'");
    }
};
