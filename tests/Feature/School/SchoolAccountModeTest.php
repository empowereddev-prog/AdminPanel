<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Services\RegisterService;
use App\Services\School\SchoolImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchoolAccountModeTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(int $role): User
    {
        $user = User::factory()->create(['user_role_id' => $role, 'status' => 'active']);
        PermissionUser::create(['user_id' => $user->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);
        return $user;
    }

    private function payload(School $school, string $mode): array
    {
        return ['school_name' => $school->name, 'school_code' => $school->school_code,
            'status' => 'active', 'subscription_type' => 'monthly', 'price' => $school->price,
            'account_mode' => $mode];
    }

    public function test_existing_default_and_public_preflight(): void
    {
        $school = School::factory()->create()->fresh();
        $this->assertSame('linked', $school->account_mode);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])
            ->assertOk()->assertJsonPath('data.account_mode', 'linked')
            ->assertJsonPath('data.parent_signup_allowed', true);
        $school->update(['account_mode' => 'independent']);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])
            ->assertOk()->assertJsonPath('data.parent_signup_allowed', false);
        $this->postJson('/api/school/account-mode', ['school_code' => 'does-not-exist'])->assertNotFound();
    }

    public function test_admin_changes_mode_without_changing_existing_users_or_limits(): void
    {
        Mail::fake();
        $school = School::factory()->create(['per_parent_child_limit' => 3]);
        $parent = User::factory()->create(['school_id' => $school->id, 'user_role_id' => 3]);
        $child = User::factory()->create(['parent_id' => $parent->id, 'user_role_id' => 4]);
        $password = $parent->password;
        $this->actingAs($this->actor(1), 'admin')->put(route('school.update', $school->id), $this->payload($school, 'independent'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('independent', $school->fresh()->account_mode);
        $this->assertSame(3, (int) $school->fresh()->per_parent_child_limit);
        $this->assertSame(100, (int) $school->fresh()->max_limit);
        $this->assertSame($password, $parent->fresh()->password);
        $this->assertSame($parent->id, (int) $child->fresh()->parent_id);
        $this->assertNull($parent->fresh()->deleted_at);
    }

    public function test_subadmin_cannot_forge_mode_change(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->actor(2), 'admin')->put(route('school.update', $school->id), $this->payload($school, 'independent'))
            ->assertForbidden();
        $this->assertSame('linked', $school->fresh()->account_mode);
    }

    public function test_both_is_reserved_until_paired_signup_is_ready(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->actor(1), 'admin')->put(route('school.update', $school->id), $this->payload($school, 'both'))
            ->assertSessionHasErrors('account_mode');
        $this->assertSame('linked', $school->fresh()->account_mode);
    }

    public function test_child_school_blocks_parent_signup_and_import_without_sending_mail(): void
    {
        Mail::fake();
        $school = School::factory()->create(['account_mode' => 'independent']);
        $before = User::count();
        $result = app(RegisterService::class)->register(['password' => 'Example@123', 'school_code' => $school->school_code]);
        $this->assertFalse($result['status']);
        $file = UploadedFile::fake()->create('parents.xlsx');
        $this->assertFalse(app(SchoolImportService::class)->importParents($school, $file)['status']);
        $this->assertSame($before, User::count());
        Mail::assertNothingSent();
    }

    public function test_admin_forms_render_and_subadmin_cannot_edit_mode(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->actor(1), 'admin')->get(route('school.create'))
            ->assertOk()->assertSee('Create School')->assertSee('Accounts &amp; capacity', false);
        $this->actingAs($this->actor(1), 'admin')->get(route('school.edit', $school->id))
            ->assertOk()->assertSee('name="account_mode"', false);
        $this->get(route('school.show', $school->id))->assertOk()->assertSee('Parent-linked Child Accounts');
        $this->actingAs($this->actor(2), 'admin')->get(route('school.edit', $school->id))
            ->assertOk()->assertDontSee('name="account_mode"', false);
    }

    public function test_parent_import_cannot_be_smuggled_through_child_school_edit(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $payload = $this->payload($school, 'independent') + ['student_excel' => UploadedFile::fake()->create('parents.xlsx')];
        $this->actingAs($this->actor(1), 'admin')->put(route('school.update', $school->id), $payload)
            ->assertSessionHasErrors('student_excel');
    }

    public function test_new_enrollment_is_blocked_but_existing_parent_profile_is_preserved(): void
    {
        Mail::fake();
        $school = School::factory()->create(['account_mode' => 'independent']);
        $parent = User::factory()->parent()->create();
        \Laravel\Passport\Passport::actingAs($parent, [], 'api');
        $this->postJson('/api/update-parent-profile', ['school_code' => $school->school_code])
            ->assertStatus(422);
        $this->assertNull($parent->fresh()->school_id);
        $parent->update(['school_id' => $school->id]);
        $this->postJson('/api/update-parent-profile', ['school_code' => $school->school_code, 'name' => 'Existing Parent'])
            ->assertOk()->assertJsonPath('status', true);
        $this->assertSame('Existing Parent', $parent->fresh()->name);
    }
}

