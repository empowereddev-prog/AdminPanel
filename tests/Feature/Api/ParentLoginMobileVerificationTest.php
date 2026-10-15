<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentLoginMobileVerificationTest extends TestCase
{
    use DatabaseTransactions;

    private function unverifiedParent(): User
    {
        // login() resolves the role id by name; a fresh database has no roles.
        if (!DB::table('roles')->where('name', 'parent')->exists()) {
            DB::table('roles')->insert(['id' => 3, 'name' => 'parent', 'title' => 'Parent']);
        }

        return User::factory()->parent()->create([
            'country_code' => '+65',
            'phone_no' => '9' . random_int(1000000, 9999999),
            'password' => 'Secret@123',
            'is_mobile_verified' => 'no',
            'school_id' => null,
        ]);
    }

    private function login(User $user, string $password = 'Secret@123')
    {
        return $this->postJson('/api/login', [
            'type' => 'parent',
            'country_code' => $user->country_code,
            'phone_no' => $user->phone_no,
            'password' => $password,
            'language' => 'english',
            'device_token' => 'test-token',
        ]);
    }

    public function test_unverified_mobile_login_stores_a_verifiable_code_and_flags_it(): void
    {
        $user = $this->unverifiedParent();

        $this->login($user)->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Please verify your mobile number ')
            ->assertJsonPath('data.requires_mobile_verification', true);

        $user->refresh();
        $this->assertNotEmpty($user->otp, 'verify-otp reads users.otp');
        $this->assertSame((string) $user->otp, (string) $user->mobile_otp);
        $this->assertTrue(\Carbon\Carbon::parse($user->otp_expires_at)->isFuture());

        // The code the login just stored is the one verify-otp accepts.
        $this->postJson('/api/verify-otp', [
            'country_code' => $user->country_code,
            'phone_no' => $user->phone_no,
            'otp' => (string) $user->otp,
            'type' => 'parent',
            'language' => 'english',
        ])->assertOk()->assertJsonPath('status', true);
        $this->assertSame('yes', $user->fresh()->is_mobile_verified);
    }

    public function test_wrong_password_does_not_flag_or_store_a_code(): void
    {
        $user = $this->unverifiedParent();

        $this->login($user, 'Wrong@123')->assertOk()
            ->assertJsonPath('status', false)
            ->assertJsonMissingPath('data.requires_mobile_verification');
        $this->assertEmpty($user->fresh()->otp);
    }
}
