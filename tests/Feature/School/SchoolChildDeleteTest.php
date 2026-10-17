<?php

namespace Tests\Feature\School;

use App\Models\{DeviceToken, PermissionUser, School, User};
use App\Services\School\SchoolSeatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SchoolChildDeleteTest extends TestCase
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
            'parent_id' => null, 'status' => 'active', 'username' => 'del_'.uniqid(), 'dob' => '2013-05']);
    }

    private function destroy(User $admin, School $school, User $child)
    {
        return $this->actingAs($admin, 'admin')
            ->deleteJson(route('school.children.destroy', [$school->id, $child->id]));
    }

    public function test_deletes_a_parent_linked_child_and_leaves_the_parent(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $child = User::factory()->child($parent)->create();
        DeviceToken::create(['user_id' => $child->id, 'device_token' => 'tok', 'device_type' => 'ios']);

        $this->destroy($this->admin(), $school, $child)->assertOk()->assertJson(['status' => true]);

        $this->assertSoftDeleted('users', ['id' => $child->id]);
        $this->assertNotSoftDeleted('users', ['id' => $parent->id]);
        $this->assertDatabaseMissing('device_tokens', ['user_id' => $child->id]);
    }

    public function test_deletes_an_independent_child_and_frees_the_seat(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $child = $this->independent($school);
        $seats = new SchoolSeatService();
        $before = $seats->childCountForSchool($school->id);

        $this->destroy($this->admin(), $school, $child)->assertOk();

        $this->assertSoftDeleted('users', ['id' => $child->id]);
        $this->assertSame($before - 1, $seats->childCountForSchool($school->id));
    }

    public function test_a_child_from_another_school_is_not_found_and_not_deleted(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $other = School::factory()->create(['account_mode' => 'independent']);
        $child = $this->independent($other);

        $this->destroy($this->admin(), $school, $child)->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $child->id]);
    }

    public function test_view_only_admin_cannot_delete(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $child = $this->independent($school);

        $this->destroy($this->admin('no'), $school, $child)->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $child->id]);
    }
}
