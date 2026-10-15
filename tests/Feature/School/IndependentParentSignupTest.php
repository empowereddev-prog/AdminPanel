<?php

namespace Tests\Feature\School;

use App\Models\{School, SchoolParentInvite, User};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class IndependentParentSignupTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function payload(School $school): array
    {
        return ['name' => 'Test Parent', 'email' => 'independent_'.uniqid().'@example.test',
            'country_code' => '+65', 'phone_no' => (string) fake()->unique()->numerify('8#######'),
            'password' => 'Example@123', 'user_type' => 'parent', 'terms_n_conditions_accepted' => 'yes',
            'language' => 'english', 'school_code' => $school->school_code];
    }

    public function test_independent_school_allows_parent_registration(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'self_signup_enabled' => 'yes']);
        $payload = $this->payload($school);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])->assertOk()
            ->assertJsonPath('data.account_mode', 'independent')->assertJsonPath('data.parent_signup_allowed', true)
            ->assertJsonPath('data.child_creation_mode', 'school_import');
        $this->postJson('/api/register', $payload)->assertOk()->assertJsonPath('status', true);
        $this->assertDatabaseHas('users', ['email' => $payload['email'], 'school_id' => $school->id, 'user_role_id' => 3]);
    }

    public function test_independent_school_with_signup_disabled_still_rejects_parent_registration(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'self_signup_enabled' => 'no']);
        $payload = $this->payload($school);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])->assertOk()
            ->assertJsonPath('data.parent_signup_allowed', false);
        $this->postJson('/api/register', $payload)->assertOk()->assertJsonPath('status', false);
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        Mail::assertNothingSent();
    }

    public function test_independent_school_still_requires_rostered_email_when_enabled(): void
    {
        $school = School::factory()->enforcingRoster()->create(['account_mode' => 'independent']);
        $payload = $this->payload($school);
        $this->postJson('/api/register', $payload)->assertOk()->assertJsonPath('status', false);
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        Mail::assertNothingSent();
    }

    public function test_rostered_parent_can_register_and_claim_invite_for_independent_school(): void
    {
        $school = School::factory()->enforcingRoster()->create(['account_mode' => 'independent']);
        $payload = $this->payload($school);
        $invite = SchoolParentInvite::factory()->for($school)->forEmail($payload['email'])->create();
        $this->postJson('/api/register', $payload)->assertOk()->assertJsonPath('status', true);
        $user = User::where('email', $payload['email'])->firstOrFail();
        $this->assertSame($school->id, (int) $user->school_id);
        $this->assertSame($user->id, (int) $invite->fresh()->claimed_user_id);
    }

    public function test_inactive_school_still_rejects_parent_registration(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'status' => 'inactive']);
        $payload = $this->payload($school);
        $this->postJson('/api/school/account-mode', ['school_code' => $school->school_code])->assertNotFound();
        $this->postJson('/api/register', $payload)->assertOk()->assertJsonPath('status', false);
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
        Mail::assertNothingSent();
    }
}