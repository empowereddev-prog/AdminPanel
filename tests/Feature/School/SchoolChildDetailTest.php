<?php

namespace Tests\Feature\School;

use App\Models\{Category, PermissionUser, School, User, UserContentWatchHistory, VideoContent};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolChildDetailTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(string $modify = 'yes'): User
    {
        $user = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $user->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => $modify]);
        return $user;
    }

    private function independent(School $school): User
    {
        return User::factory()->create(['user_role_id' => 4, 'user_type' => 'child', 'school_id' => $school->id,
            'parent_id' => null, 'status' => 'active', 'username' => 'detail_'.uniqid(), 'dob' => '2013-05']);
    }

    public function test_independent_child_details_show_policies_and_their_video_progress(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $child = $this->independent($school);
        $child->update(['popup_1_updated_at' => '2026-10-15 12:44:00', 'popup_2_updated_at' => null]);
        $category = Category::create(['category_name' => 'Child detail progress', 'status' => 'active']);
        $video = VideoContent::create(['category_id' => $category->id, 'title' => 'Child detail video',
            'description' => 'Test', 'user_type' => 'child', 'video_duration' => '10:00', 'status' => 'active']);
        // Local legacy schema still references children.id; the application API
        // stores users.id in child_id. Supply the corresponding legacy fixture.
        DB::table('children')->insert(['id' => $child->id, 'name' => 'Progress fixture',
            'parent_id' => $school->user_id, 'dob' => '2013-05-01', 'status' => 'active']);
        UserContentWatchHistory::create(['child_id' => $child->id, 'video_content_id' => $video->id,
            'last_watched_duration' => '05:00', 'total_video_duration' => '10:00', 'is_completed' => 'no']);
        $other = $this->independent($school);
        DB::table('children')->insert(['id' => $other->id, 'name' => 'Other progress fixture',
            'parent_id' => $school->user_id, 'dob' => '2013-05-01', 'status' => 'active']);
        UserContentWatchHistory::create(['child_id' => $other->id, 'video_content_id' => $video->id,
            'last_watched_duration' => '10:00', 'total_video_duration' => '10:00', 'is_completed' => 'yes']);
        $this->actingAs($this->admin(), 'admin')->get(route('school.children.show', [$school->id, $child->id]))
            ->assertOk()->assertSee($child->name)->assertSee($child->username)->assertSee('Independent')
            ->assertSee('Policy Verification Details')->assertSee('15 Oct 2026, 12:44 PM')->assertSee('Not Verified')
            ->assertSee('Video Progress by Category')->assertSee('Quiz Progress by Category')->assertSee('Child detail progress')
            ->assertViewHas('child', fn ($data) => $data->categoryProgress[$category->id]['percentage'] == 50);
    }

    public function test_linked_child_uses_their_parents_school_and_shows_parent_details(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $child = User::factory()->child($parent)->create();
        $this->actingAs($this->admin(), 'admin')->get(route('school.children.show', [$school->id, $child->id]))
            ->assertOk()->assertSee('Parent-linked')->assertSee($parent->name)->assertSee($parent->email);
    }

    public function test_another_schools_child_and_non_child_accounts_are_not_accessible(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $child = $this->independent($otherSchool);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $admin = $this->admin();
        foreach ([$child, $parent] as $user) {
            $this->actingAs($admin, 'admin')->get(route('school.children.show', [$school->id, $user->id]))->assertNotFound();
        }
    }

    public function test_deleted_children_and_children_of_deleted_parents_are_not_accessible(): void
    {
        $school = School::factory()->create();
        $child = $this->independent($school);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $linked = User::factory()->child($parent)->create();
        $child->delete();
        $parent->delete();
        $admin = $this->admin();
        foreach ([$child, $linked] as $user) {
            $this->actingAs($admin, 'admin')->get(route('school.children.show', [$school->id, $user->id]))->assertNotFound();
        }
    }

    public function test_school_permission_is_required(): void
    {
        $school = School::factory()->create();
        $child = $this->independent($school);
        $this->actingAs($this->admin('no'), 'admin')->get(route('school.children.show', [$school->id, $child->id]))->assertForbidden();
    }

    public function test_listing_contains_a_school_scoped_view_details_action(): void
    {
        $school = School::factory()->create();
        $child = $this->independent($school);
        $rows = $this->actingAs($this->admin(), 'admin')->getJson(route('school.children.data', $school->id))
            ->assertOk()->json('data');
        $this->assertStringContainsString('mdi-eye', $rows[0]['action']);
        $this->assertStringNotContainsString('>View Details</a>', $rows[0]['action']);
        $this->assertStringContainsString(route('school.children.show', [$school->id, $child->id], false), $rows[0]['action']);
    }
}