<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * School roster + child seat controls, part 2 of 2.
 *
 * The school's list of parent email addresses is the one authoritative fact in
 * the onboarding flow, and today it is consumed once by the Excel importer and
 * then thrown away. That is why a shared school_code grants access to anyone:
 * there is no list left to check a self-signup against.
 *
 * This table persists it. Membership becomes "is this email on the school's
 * roster?" rather than "knows a secret", and school_code degrades to a routing
 * hint that is worthless on its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_parent_invites')) {
            return;
        }

        Schema::create('school_parent_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            // 191, matching schools.school_code, so the composite unique below
            // fits in the index limit under utf8mb4 on older MySQL.
            // Always stored through SchoolParentInvite::normaliseEmail().
            $table->string('email', 191);

            $table->string('name')->nullable();
            $table->string('country_code', 10)->nullable();
            $table->string('phone_no', 20)->nullable();

            $table->enum('status', ['invited', 'claimed', 'revoked'])->default('invited');

            // Deliberately NOT a constrained foreign key. users uses SoftDeletes,
            // so a cascade would hard-delete invite rows when a user is force
            // deleted, and restrictOnDelete would block
            // SchoolController::destroySchoolUser. The relation resolves it.
            $table->unsignedBigInteger('claimed_user_id')->nullable();

            // Optional per-parent override of schools.per_parent_child_limit.
            $table->unsignedSmallInteger('child_seat_allocation')->nullable();

            $table->timestamp('invited_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            // The single-claim property rests on this: one row per school per
            // email, so a leaked roster address cannot be claimed twice.
            $table->unique(['school_id', 'email'], 'spi_school_email_unique');
            $table->index('email', 'spi_email_index');
            $table->index(['school_id', 'status'], 'spi_school_status_index');
            $table->index('claimed_user_id', 'spi_claimed_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_parent_invites');
    }
};
