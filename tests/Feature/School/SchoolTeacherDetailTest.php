<?php

namespace Tests\Feature\School;

use App\Models\{PermissionUser, School, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolTeacherDetailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['scope.school_extras' => true]);
    }

    private function admin(string $modify = 'yes'): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => $modify]);
        return $admin;
    }

    public function test_teacher_details_use_school_permission_and_show_teacher_information(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->inSchool($school)->create();
        $this->actingAs($this->admin(), 'admin')->get(route('school.teachers.show', [$school->id, $teacher->id]))
            ->assertOk()->assertSee('Teacher Details')->assertSee($teacher->name)->assertSee($teacher->email)
            ->assertSee($teacher->username)->assertSee('Video Progress by Category')->assertSee('Back to School')
            ->assertDontSee('Parental consent policy');
    }

    public function test_other_school_teachers_non_teachers_and_deleted_teachers_are_rejected(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $other = User::factory()->teacher()->inSchool($otherSchool)->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $deleted = User::factory()->teacher()->inSchool($school)->create();
        $deleted->delete();
        $admin = $this->admin();
        foreach ([$other, $parent, $deleted] as $user) {
            $this->actingAs($admin, 'admin')->get(route('school.teachers.show', [$school->id, $user->id]))->assertNotFound();
        }
    }

    public function test_teacher_detail_requires_existing_school_permissions(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->inSchool($school)->create();
        $this->actingAs($this->admin('no'), 'admin')->get(route('school.teachers.show', [$school->id, $teacher->id]))->assertForbidden();
    }

    public function test_teacher_feed_has_eye_action_and_keeps_remove_action(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->inSchool($school)->create();
        $rows = $this->actingAs($this->admin(), 'admin')->getJson(route('school.teachers.data', $school->id))
            ->assertOk()->json('data');
        $this->assertStringContainsString('mdi-eye', $rows[0]['action']);
        $this->assertStringContainsString(route('school.teachers.show', [$school->id, $teacher->id], false), $rows[0]['action']);
        $this->assertStringContainsString('teacher-delete', $rows[0]['action']);
    }

    public function test_teacher_detail_respects_school_extras_gate(): void
    {
        config(['scope.school_extras' => false]);
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->inSchool($school)->create();
        $this->actingAs($this->admin(), 'admin')->get(route('school.teachers.show', [$school->id, $teacher->id]))->assertRedirect(route('school.index'));
    }
}