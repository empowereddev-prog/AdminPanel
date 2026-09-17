<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Enabling enforce_parent_roster on a school whose parents are not on the
 * roster locks every one of them out. This command is what prevents that, so
 * its idempotency and its verification exit code both need pinning.
 */
class BackfillSchoolRosterTest extends TestCase
{
    use DatabaseTransactions;

    private function backfill(School $school, array $options = []): int
    {
        return $this->artisan('school:backfill-roster', array_merge(['--school' => $school->id], $options))->run();
    }

    public function test_it_creates_one_claimed_invite_per_school_parent(): void
    {
        $school = School::factory()->create();
        $parents = User::factory()->count(3)->parent()->inSchool($school)->create();

        $this->assertSame(0, $this->backfill($school));

        $this->assertSame(3, SchoolParentInvite::where('school_id', $school->id)->count());

        foreach ($parents as $parent) {
            $this->assertDatabaseHas('school_parent_invites', [
                'school_id' => $school->id,
                'email' => SchoolParentInvite::normaliseEmail($parent->email),
                'status' => 'claimed',
                'claimed_user_id' => $parent->id,
            ]);
        }
    }

    public function test_rerunning_changes_nothing(): void
    {
        $school = School::factory()->create();
        User::factory()->count(3)->parent()->inSchool($school)->create();

        $this->backfill($school);
        $first = SchoolParentInvite::where('school_id', $school->id)->get()->toArray();

        $this->backfill($school);
        $second = SchoolParentInvite::where('school_id', $school->id)->get()->toArray();

        $this->assertSame(3, count($second));
        $this->assertEquals($first, $second, 'A rerun must be a no-op.');
    }

    public function test_teachers_are_not_added_to_the_parent_roster(): void
    {
        $school = School::factory()->create();
        User::factory()->parent()->inSchool($school)->create();
        User::factory()->teacher()->inSchool($school)->create();

        $this->backfill($school);

        $this->assertSame(1, SchoolParentInvite::where('school_id', $school->id)->count());
    }

    public function test_soft_deleted_parents_are_skipped(): void
    {
        $school = School::factory()->create();
        User::factory()->parent()->inSchool($school)->create();
        User::factory()->parent()->inSchool($school)->create()->delete();

        $this->backfill($school);

        $this->assertSame(1, SchoolParentInvite::where('school_id', $school->id)->count());
    }

    /** An admin's revocation must survive a rerun, or revoking is meaningless. */
    public function test_a_revoked_entry_is_not_resurrected(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();

        SchoolParentInvite::factory()->for($school)->forEmail($parent->email)->revoked()->create();

        $this->backfill($school);

        $this->assertDatabaseHas('school_parent_invites', [
            'school_id' => $school->id,
            'email' => SchoolParentInvite::normaliseEmail($parent->email),
            'status' => 'revoked',
        ]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $school = School::factory()->create();
        User::factory()->count(2)->parent()->inSchool($school)->create();

        $this->backfill($school, ['--dry-run' => true]);

        $this->assertSame(0, SchoolParentInvite::where('school_id', $school->id)->count());
    }

    /**
     * Two parents sharing one address cannot both own the single roster row, so
     * the command must refuse rather than silently pick a winner.
     */
    public function test_duplicate_emails_within_a_school_fail_verification(): void
    {
        $school = School::factory()->create();
        $shared = 'shared@example.test';

        User::factory()->parent()->inSchool($school)->create(['email' => $shared]);
        User::factory()->parent()->inSchool($school)->create(['email' => strtoupper($shared)]);

        $this->assertSame(1, $this->backfill($school), 'A duplicate must exit non-zero so a deploy stops on it.');
    }

    public function test_mixed_case_emails_are_normalised(): void
    {
        $school = School::factory()->create();
        User::factory()->parent()->inSchool($school)->create(['email' => 'Mixed.Case@Example.Test']);

        $this->backfill($school);

        $this->assertDatabaseHas('school_parent_invites', ['email' => 'mixed.case@example.test']);
    }
}
