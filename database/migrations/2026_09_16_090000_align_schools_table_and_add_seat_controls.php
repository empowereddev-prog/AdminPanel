<?php

use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School roster + child seat controls, part 1 of 2.
 *
 * Two jobs, both additive and both guarded so this is safe on a database that
 * was hand-patched in production:
 *
 * 1. Backfill the three `schools` columns the application has always written
 *    but no migration ever created (`max_limit`, `subscription_type`, `email`).
 *    SchoolController::store writes all three and edit.blade.php renders
 *    max_limit, yet create_schools_table only makes id/name/school_code/
 *    user_id/status/timestamps - so `migrate:fresh` has been breaking school
 *    creation outright.
 *
 * 2. Add the seat and roster controls. Every flag defaults to today's
 *    behaviour, so running this migration changes nothing on its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->alignHandPatchedColumns();
        $this->addSeatAndRosterControls();
        $this->addSeatCountIndexes();
        $this->seedSchoolEmailTemplates();
    }

    public function down(): void
    {
        // Only the columns THIS migration introduced are dropped. max_limit,
        // subscription_type and email predate it in production - dropping them
        // on a rollback would destroy live data that the app depends on.
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                foreach ([
                    'child_seat_limit',
                    'per_parent_child_limit',
                    'enforce_parent_roster',
                    'self_signup_enabled',
                    'code_rotated_at',
                ] as $column) {
                    if (Schema::hasColumn('schools', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['users_parent_id_index', 'users_school_id_index'] as $index) {
                    if ($this->indexExists('users', $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }

    /**
     * Columns the code has always written against a schema that never declared
     * them. Nullable throughout: existing rows have no value to backfill with,
     * and store() validates max_limit/subscription_type/email at the request
     * layer anyway.
     */
    private function alignHandPatchedColumns(): void
    {
        if (!Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'email')) {
                $table->string('email', 191)->nullable()->after('name');
            }

            if (!Schema::hasColumn('schools', 'max_limit')) {
                $table->unsignedInteger('max_limit')->nullable()->after('status');
            }

            if (!Schema::hasColumn('schools', 'subscription_type')) {
                // Deliberately a plain string, not an enum. subscriptions.subscription_type
                // is enum('monthly','quaterly','yearly') - misspelled - and both school
                // forms submit 'quarterly'. Pinning an enum here would hard-fail every
                // quarterly school before that typo is fixed on its own ticket.
                $table->string('subscription_type', 20)->nullable()->after('max_limit');
            }
        });
    }

    private function addSeatAndRosterControls(): void
    {
        if (!Schema::hasTable('schools')) {
            return;
        }

        Schema::table('schools', function (Blueprint $table) {
            // NULL means unenforced, which is today's behaviour. The agreed
            // "default 3" for per_parent_child_limit is a form pre-fill for
            // newly created schools only - a column default would instantly cap
            // every existing school's parents at 3 children on migrate.
            if (!Schema::hasColumn('schools', 'child_seat_limit')) {
                $table->unsignedInteger('child_seat_limit')->nullable()->after('max_limit');
            }

            if (!Schema::hasColumn('schools', 'per_parent_child_limit')) {
                $table->unsignedSmallInteger('per_parent_child_limit')->nullable()->after('child_seat_limit');
            }

            // enum('yes','no') matches every other flag column in this schema
            // (permission_users.is_modify, children.is_primary, users.is_first_login).
            if (!Schema::hasColumn('schools', 'enforce_parent_roster')) {
                $table->enum('enforce_parent_roster', ['yes', 'no'])
                    ->default('no')
                    ->after('per_parent_child_limit');
            }

            if (!Schema::hasColumn('schools', 'self_signup_enabled')) {
                $table->enum('self_signup_enabled', ['yes', 'no'])
                    ->default('yes')
                    ->after('enforce_parent_roster');
            }

            if (!Schema::hasColumn('schools', 'code_rotated_at')) {
                $table->timestamp('code_rotated_at')->nullable()->after('self_signup_enabled');
            }
        });
    }

    /**
     * SchoolSeatService counts a school's children by self-joining users on
     * parent_id, and that runs on every addChild for a school parent.
     * parent_id is added unindexed by 2026_09_11_090000, and school_id has
     * only its foreign key - so without these the seat cap is a latency
     * regression on the hot path.
     */
    private function addSeatCountIndexes(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parent_id') && !$this->indexExists('users', 'users_parent_id_index')) {
                $table->index('parent_id', 'users_parent_id_index');
            }

            if (Schema::hasColumn('users', 'school_id') && !$this->indexExists('users', 'users_school_id_index')) {
                $table->index('school_id', 'users_school_id_index');
            }
        });
    }

    /**
     * signup_school_user and signup_teacher have had call sites and no template
     * for as long as the importer has existed, so every bulk-imported parent
     * and teacher has been silently receiving nothing. EmailTemplateController
     * cannot create a template - create() and store() are empty stubs - so a
     * seeder invoked from a migration is the only way these rows can appear.
     * Same approach 2026_09_11_090000 takes for admin_otp.
     */
    private function seedSchoolEmailTemplates(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        (new SchoolEmailTemplateSeeder())->run();
    }

    /**
     * Schema::hasIndex only exists from Laravel 11.15 on some drivers; this
     * mirrors the information_schema probe 2026_09_11_090000 uses for enums.
     */
    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            "SELECT 1 AS found FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1",
            [$table, $index]
        ) !== null;
    }
};
