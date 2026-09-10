<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the users table in line with what the application actually reads and
 * writes.
 *
 * Children were moved from the `children` table onto `users` (ChildController
 * ::addChild creates User rows with user_role_id = 4), but no migration ever
 * added the columns that move needs. On a database built from this repo,
 * addChild fails outright with "Unknown column 'username' in 'field list'",
 * and several other endpoints read columns that do not exist. Production must
 * have been altered by hand, so every change here is guarded and safe to
 * re-run against a database that already has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Written by ChildController::addChild, SchoolController and
            // UpdateTeacherProfileRequest (which also declares a unique rule).
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            // Stored as 'YYYY-MM' - addChild validates date_format:Y-m and the
            // age gates parse it with Carbon::createFromFormat('Y-m', ...).
            if (!Schema::hasColumn('users', 'dob')) {
                $table->string('dob', 7)->nullable()->after('gender');
            }

            // Incremented in whole points but also accumulated from
            // round($watchPercent * $videoPoint, 2), so it needs decimals.
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

        // users.user_type had no 'child' value, yet addChild writes 'child'.
        if (!$this->enumHasValue('users', 'user_type', 'child')) {
            DB::statement(
                "ALTER TABLE `users` MODIFY `user_type` "
                . "ENUM('user','admin','parent','teacher','child') NULL DEFAULT NULL"
            );
        }

        // is_mobile_verified was enum('0','1') while every reader and writer in
        // the app uses 'yes'/'no' - so writes were rejected and
        // where('is_mobile_verified','yes') matched nothing. Widen, translate
        // the existing rows, then narrow onto the values the code uses.
        if (!$this->enumHasValue('users', 'is_mobile_verified', 'yes')) {
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` VARCHAR(10) NOT NULL DEFAULT 'no'");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = 'yes' WHERE `is_mobile_verified` IN ('1', 'yes')");
            DB::statement("UPDATE `users` SET `is_mobile_verified` = 'no'  WHERE `is_mobile_verified` NOT IN ('yes')");
            DB::statement("ALTER TABLE `users` MODIFY `is_mobile_verified` ENUM('yes','no') NOT NULL DEFAULT 'no'");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['is_mood_updated', 'is_avatar_primary', 'is_first_login', 'avtar_image', 'loyalty_points', 'dob'] as $column) {
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

        DB::statement(
            "ALTER TABLE `users` MODIFY `user_type` "
            . "ENUM('user','admin','parent','teacher') NULL DEFAULT NULL"
        );
    }

    /**
     * True when an enum column already offers the given value, so the ALTERs
     * above stay idempotent on databases that were patched by hand.
     */
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
