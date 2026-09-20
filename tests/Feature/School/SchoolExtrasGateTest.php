<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The scope.school_extras gate - see config/scope.php.
 *
 * Hiding a control is only half the job: the route behind it stays reachable
 * from a bookmark unless something closes it. These pin both halves, and pin
 * what must stay reachable, because over-blocking would take the contracted
 * work down along with the extras.
 */
class SchoolExtrasGateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['scope.school_extras' => false]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'user_role_id' => 1,
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        // 3 = School Management, 6 = Email Template.
        foreach ([3, 6] as $menuId) {
            PermissionUser::create([
                'user_id' => $admin->id,
                'menu_id' => $menuId,
                'is_view' => 'yes',
                'is_modify' => 'yes',
            ]);
        }

        return $admin;
    }

    public function test_gated_school_screens_redirect_to_the_school_list(): void
    {
        $school = School::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get(route('school.moods.overview', [], false))
            ->assertRedirect(route('school.index'));

        $this->actingAs($admin, 'admin')
            ->get(route('school.children.progress', $school->id, false))
            ->assertRedirect(route('school.index'));
    }

    public function test_gated_roster_endpoints_are_closed_to_ajax(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.teachers.data', $school->id, false))
            ->assertStatus(404);
    }

    /** The school module itself is contracted work and must stay reachable. */
    public function test_core_school_screens_stay_available(): void
    {
        $school = School::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('school.index', [], false))
            ->assertOk()
            ->assertDontSee('Show Moods', false);
        $this->actingAs($admin, 'admin')->get(route('school.show', $school->id, false))->assertOk();
        $this->actingAs($admin, 'admin')
            ->getJson(route('school.roster.data', $school->id, false))
            ->assertOk();
    }

    /**
     * The two access-control switches govern live signup behaviour, so they are
     * deliberately left visible and working while the extras are hidden.
     */
    public function test_the_school_access_flags_still_toggle(): void
    {
        $school = School::factory()->create(['self_signup_enabled' => 'yes']);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('school.flag.toggle', [$school->id, 'self_signup_enabled'], false))
            ->assertOk();

        $this->assertSame('no', $school->fresh()->self_signup_enabled);
    }

    public function test_the_contracted_templates_stay_editable_and_the_extras_do_not(): void
    {
        (new SchoolEmailTemplateSeeder())->run();
        $admin = $this->admin();

        // The two contracted registration emails.
        $this->actingAs($admin, 'admin')
            ->get(url('email-template/signup_school_user/edit'))
            ->assertOk();
        $this->actingAs($admin, 'admin')
            ->get(url('email-template/signup_teacher/edit'))
            ->assertOk();

        // An extra: its edit URL is closed even though the row still exists.
        $this->actingAs($admin, 'admin')
            ->get(url('email-template/school_onboarded/edit'))
            ->assertRedirect(route('email-template.index'));

        // The controller branches on $request->ajax(), which reads
        // X-Requested-With rather than the Accept header.
        $listed = collect(
            $this->actingAs($admin, 'admin')
                ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
                ->getJson(route('email-template.index'))
                ->json('data')
        )->pluck('variable_name')->all();

        $this->assertNotContains('school_onboarded', $listed);
        $this->assertNotContains('school_seat_threshold', $listed);
    }

    /**
     * What the client actually sees on the school screen: the gated blocks are
     * absent from the markup, and the contracted surface is untouched.
     */
    public function test_the_school_view_hides_the_extras_but_keeps_the_rest(): void
    {
        $school = School::factory()->create();

        $page = $this->actingAs($this->admin(), 'admin')
            ->get(route('school.show', $school->id, false))
            ->assertOk();

        foreach ([
            'Teacher Roster',
            'Import teachers',
            'Parent Limit',
            'Child Places',
            'Children Per Parent',
            'Students Report',
            'roster-revoke',
            'parent-status',
        ] as $hidden) {
            $page->assertDontSee($hidden, false);
        }

        foreach ([
            'School Code',
            'Import parents',
            'Only roster emails may join',
            'Allow sign-up with school code',
        ] as $kept) {
            $page->assertSee($kept, false);
        }
    }

    public function test_the_school_view_shows_the_extras_again_when_enabled(): void
    {
        config(['scope.school_extras' => true]);
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('school.show', $school->id, false))
            ->assertOk()
            ->assertSee('Teacher Roster', false)
            ->assertSee('Parent Limit', false)
            ->assertSee('Students Report', false);
    }

    /** Nothing is deleted - flipping the flag restores every surface. */
    public function test_turning_the_flag_on_restores_the_extras(): void
    {
        config(['scope.school_extras' => true]);
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.teachers.data', $school->id, false))
            ->assertOk();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('school.moods.overview', [], false))
            ->assertOk();
    }
}
