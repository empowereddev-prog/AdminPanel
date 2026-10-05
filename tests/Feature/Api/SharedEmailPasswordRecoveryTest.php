<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SharedEmailPasswordRecoveryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        (new SchoolEmailTemplateSeeder())->run();
        Mail::fake();
    }

    private function accounts(): array
    {
        $parent = User::factory()->parent()->create();
        $teacher = User::factory()->teacher()->create(['email' => $parent->email]);

        return [$parent, $teacher];
    }

    public function test_shared_email_requires_account_selection(): void
    {
        [$parent, $teacher] = $this->accounts();

        $this->postJson('/api/forgot-password', ['email' => $parent->email])
            ->assertOk()->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Multiple accounts use this email. Provide your account type or username.');

        $this->assertNull($parent->fresh()->password_reset_code);
        $this->assertNull($teacher->fresh()->password_reset_code);
        Mail::assertNothingSent();
    }

    public function test_teacher_recovery_and_web_reset_leave_parent_unchanged(): void
    {
        [$parent, $teacher] = $this->accounts();
        $parentPassword = $parent->password;

        $this->postJson('/api/forgot-password', ['email' => $teacher->email, 'type' => 'teacher'])
            ->assertOk()->assertJsonPath('status', true);
        $token = $teacher->fresh()->password_reset_code;
        $this->assertNotNull($token);
        $this->assertNull($parent->fresh()->password_reset_code);
        $this->get(route('reset.password.page', $token))->assertOk()->assertDontSee($teacher->password);

        $this->post(route('password-reset', $token), ['password' => 'NewTeacher1!', 'confirm_password' => 'NewTeacher1!'])
            ->assertRedirect(route('success'));

        $this->assertTrue(Hash::check('NewTeacher1!', $teacher->fresh()->password));
        $this->assertSame($parentPassword, $parent->fresh()->password);
        $this->assertNull($teacher->fresh()->password_reset_code);
        $this->assertNull($teacher->fresh()->password_reset_expires_at);

        $this->post(route('password-reset', $token), ['password' => 'AnotherPass1!', 'confirm_password' => 'AnotherPass1!'])
            ->assertSessionHas('fail');
        $this->assertTrue(Hash::check('NewTeacher1!', $teacher->fresh()->password));
    }

    public function test_username_selects_only_its_account_for_api_reset(): void
    {
        [$parent, $teacher] = $this->accounts();
        $parentPassword = $parent->password;

        $this->postJson('/api/forgot-password', ['email' => $teacher->email, 'username' => $teacher->username])
            ->assertOk()->assertJsonPath('status', true);
        $token = $teacher->fresh()->password_reset_code;
        $this->postJson('/api/reset-password/' . $token, ['password' => 'NewTeacher1!', 'confirm_password' => 'NewTeacher1!'])
            ->assertOk()->assertJsonPath('status', true);

        $this->assertSame($parentPassword, $parent->fresh()->password);
        $this->assertTrue(Hash::check('NewTeacher1!', $teacher->fresh()->password));
        $this->assertNull($teacher->fresh()->password_reset_code);
    }

    public function test_parent_recovery_still_works_with_a_shared_email(): void
    {
        [$parent, $teacher] = $this->accounts();
        $teacherPassword = $teacher->password;

        $this->postJson('/api/forgot-password', ['email' => $parent->email, 'type' => 'parent'])
            ->assertOk()->assertJsonPath('status', true);
        $token = $parent->fresh()->password_reset_code;
        $this->post(route('password-reset', $token), ['password' => 'NewParent1!', 'confirm_password' => 'NewParent1!'])
            ->assertRedirect(route('success'));

        $this->assertTrue(Hash::check('NewParent1!', $parent->fresh()->password));
        $this->assertSame($teacherPassword, $teacher->fresh()->password);
    }

    public function test_unique_email_remains_compatible_with_email_only_recovery(): void
    {
        $parent = User::factory()->parent()->create();
        $this->postJson('/api/forgot-password', ['email' => $parent->email])
            ->assertOk()->assertJsonPath('status', true);
        $this->assertNotNull($parent->fresh()->password_reset_code);
    }

    public function test_expired_tokens_cannot_reset_shared_email_accounts(): void
    {
        [$parent, $teacher] = $this->accounts();
        $teacher->update(['password_reset_code' => 'expired-' . uniqid(), 'password_reset_expires_at' => now()->subMinute()]);
        $original = $teacher->password;
        $payload = ['password' => 'NewTeacher1!', 'confirm_password' => 'NewTeacher1!'];

        $this->post(route('password-reset', $teacher->password_reset_code), $payload)->assertSessionHas('fail');
        $this->postJson('/api/reset-password/' . $teacher->password_reset_code, $payload)
            ->assertOk()->assertJsonPath('status', false);
        $this->assertSame($original, $teacher->fresh()->password);
    }
}
