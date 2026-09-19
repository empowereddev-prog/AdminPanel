<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The admin side of the roster. The case that matters most is the guard on
 * enabling enforcement: switching it on for a school whose parents are not yet
 * on the roster would lock out every one of them.
 */
class SchoolRosterAdminTest extends TestCase
{
    use DatabaseTransactions;

    private const SCHOOL_MENU_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function admin(string $isModify = 'yes'): User
    {
        $admin = User::factory()->create([
            'user_role_id' => 2,
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        PermissionUser::create([
            'user_id' => $admin->id,
            'menu_id' => self::SCHOOL_MENU_ID,
            'is_view' => 'yes',
            'is_modify' => $isModify,
        ]);

        return $admin;
    }

    public function test_a_view_only_admin_cannot_change_the_roster(): void
    {
        $school = School::factory()->create();
        $invite = SchoolParentInvite::factory()->for($school)->create();

        $this->actingAs($this->admin('no'), 'admin')
            ->postJson(route('school.roster.revoke', $invite->id))
            ->assertStatus(403);

        $this->assertSame('invited', $invite->fresh()->status, 'A view-only admin must not be able to revoke.');
    }

    /** The roster is import-driven now; there is no manual add endpoint. */
    public function test_there_is_no_manual_invite_route(): void
    {
        $this->assertFalse(
            app('router')->getRoutes()->hasNamedRoute('school.roster.invite'),
            'The manual invite endpoint should not exist - parents come from the Excel import only.'
        );
    }

    public function test_revoking_blocks_a_parent_without_deleting_their_account(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $invite = SchoolParentInvite::factory()->for($school)->forEmail($parent->email)->claimedBy($parent)->create();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.roster.revoke', $invite->id))
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertSame('revoked', $invite->fresh()->status);
        $this->assertNotNull($parent->fresh(), 'Revoking must not delete the parent account.');
    }

    /**
     * The single guard that stops an admin locking a whole school out of the
     * app with one click.
     */
    public function test_enforcement_cannot_be_enabled_while_parents_are_off_the_roster(): void
    {
        $school = School::factory()->create();
        User::factory()->count(2)->parent()->inSchool($school)->create();

        $response = $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.flag.toggle', [$school->id, 'enforce_parent_roster']));

        $response->assertStatus(422)->assertJson(['status' => false]);
        $this->assertStringContainsString('backfill-roster', $response->json('message'));
        $this->assertSame('no', $school->fresh()->enforce_parent_roster, 'The flag must not have flipped.');
    }

    public function test_enforcement_can_be_enabled_once_the_backfill_is_clean(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        SchoolParentInvite::factory()->for($school)->forEmail($parent->email)->claimedBy($parent)->create();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.flag.toggle', [$school->id, 'enforce_parent_roster']))
            ->assertOk()
            ->assertJson(['status' => true, 'value' => 'yes']);

        $this->assertSame('yes', $school->fresh()->enforce_parent_roster);
    }

    /** Turning enforcement OFF is always safe, so it needs no backfill check. */
    public function test_enforcement_can_always_be_turned_off(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        User::factory()->parent()->inSchool($school)->create();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.flag.toggle', [$school->id, 'enforce_parent_roster']))
            ->assertOk()
            ->assertJson(['value' => 'no']);
    }

    /** The flag segment names a database column, so it must never be free text. */
    public function test_an_unknown_flag_name_is_rejected(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.flag.toggle', [$school->id, 'status']))
            ->assertStatus(422);

        $this->assertSame('active', $school->fresh()->status);
    }

    public function test_the_roster_feed_returns_the_schools_entries(): void
    {
        $school = School::factory()->create();
        SchoolParentInvite::factory()->count(2)->for($school)->create();
        SchoolParentInvite::factory()->for(School::factory()->create())->create();

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.roster.data', $school->id))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data, 'The feed must be scoped to this school.');
    }

    public function test_a_parent_without_a_roster_row_still_appears_in_the_merged_list(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create(['email' => 'orphan' . uniqid() . '@example.test']);
        SchoolParentInvite::factory()->for($school)->create();

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.roster.data', $school->id))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data);
        $this->assertTrue(
            collect($data)->contains(fn ($row) => str_contains(html_entity_decode((string) $row['email']), $parent->email))
        );
    }

    public function test_a_revoked_roster_entry_can_be_restored(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $invite = SchoolParentInvite::factory()->for($school)->forEmail($parent->email)->revoked()->create();

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.roster.restore', $invite->id))
            ->assertOk()
            ->assertJson(['status' => true]);

        $invite->refresh();
        $this->assertSame('claimed', $invite->status);
        $this->assertSame($parent->id, (int) $invite->claimed_user_id);
        $this->assertNotNull($parent->fresh());
    }

    public function test_an_inactive_parent_can_be_enabled(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create(['status' => 'inactive']);
        $child = User::factory()->child($parent)->create(['status' => 'inactive']);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.parents.status', [$school->id, $parent->id]), ['status' => 'active'])
            ->assertOk()
            ->assertJson(['status' => true, 'value' => 'active']);

        $this->assertSame('active', $parent->fresh()->status);
        $this->assertSame('active', $child->fresh()->status);
    }

    public function test_disabling_a_parent_also_disables_their_children(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create(['status' => 'active']);
        $child = User::factory()->child($parent)->create(['status' => 'active']);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.parents.status', [$school->id, $parent->id]), ['status' => 'inactive'])
            ->assertOk();

        $this->assertSame('inactive', $parent->fresh()->status);
        $this->assertSame('inactive', $child->fresh()->status);
    }
}
