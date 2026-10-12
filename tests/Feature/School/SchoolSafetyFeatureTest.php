<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SchoolSafetyFeatureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_and_individual_visibility_remain_enabled(): void
    {
        $this->assertSame('yes', School::factory()->create()->fresh()->needs_safety_feature);
        Passport::actingAs(User::factory()->parent()->create(), [], 'api');
        $this->getJson('/api/school/safety-feature')->assertOk()
            ->assertJsonPath('data.school_id', null)->assertJsonPath('data.show_get_help', true);
    }

    public function test_linked_children_inherit_parent_school_and_ignore_supplied_school_id(): void
    {
        $school = School::factory()->create(['needs_safety_feature' => 'no']);
        $other = School::factory()->create(['needs_safety_feature' => 'yes']);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $child = User::factory()->create(['user_role_id' => 4, 'parent_id' => $parent->id, 'school_id' => null, 'status' => 'active']);
        Passport::actingAs($child, [], 'api');
        $this->getJson('/api/school/safety-feature?school_id='.$other->id)->assertOk()
            ->assertJsonPath('data.school_id', $school->id)->assertJsonPath('data.needs_safety_feature', 'no')
            ->assertJsonPath('data.safety_feature_enabled', false)->assertJsonPath('data.show_get_help', false);
    }

    public function test_independent_student_uses_own_school_and_sees_latest_setting(): void
    {
        $school = School::factory()->create(['needs_safety_feature' => 'no', 'account_mode' => 'independent']);
        $child = User::factory()->create(['user_role_id' => 4, 'school_id' => $school->id, 'parent_id' => null, 'status' => 'active']);
        Passport::actingAs($child, [], 'api');
        $this->getJson('/api/school/safety-feature')->assertOk()->assertJsonPath('data.show_get_help', false);
        $school->update(['needs_safety_feature' => 'yes']);
        $this->getJson('/api/school/safety-feature')->assertOk()->assertJsonPath('data.show_get_help', true);
    }

    public function test_only_admin_can_save_valid_safety_setting(): void
    {
        Mail::fake();
        $school = School::factory()->create();
        $payload = ['school_name' => $school->name, 'school_code' => $school->school_code,
            'status' => 'active', 'subscription_type' => 'monthly', 'needs_safety_feature' => 'no'];
        $admin = User::factory()->create(['user_role_id' => 1, 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);
        $this->actingAs($admin, 'admin')->put(route('school.update', $school->id), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame('no', $school->fresh()->needs_safety_feature);
        $this->put(route('school.update', $school->id), array_merge($payload, ['needs_safety_feature' => 'invalid']))
            ->assertSessionHasErrors('needs_safety_feature');
        $sub = User::factory()->create(['user_role_id' => 2, 'status' => 'active']);
        PermissionUser::create(['user_id' => $sub->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);
        $this->actingAs($sub, 'admin')->put(route('school.update', $school->id), array_merge($payload, ['needs_safety_feature' => 'yes']))
            ->assertForbidden();
        $this->assertSame('no', $school->fresh()->needs_safety_feature);
    }

    public function test_prelogin_lookup_and_admin_forms_expose_setting(): void
    {
        $school = School::factory()->create(['needs_safety_feature' => 'no']);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])->assertOk()
            ->assertJsonPath('data.needs_safety_feature', 'no')->assertJsonPath('data.safety_feature_enabled', false);
        $admin = User::factory()->create(['user_role_id' => 1, 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);
        $this->actingAs($admin, 'admin')->get(route('school.create'))->assertOk()->assertSee('name="needs_safety_feature"', false);
        $this->get(route('school.edit', $school->id))->assertOk()->assertSee('value="no" selected', false);
    }
}
