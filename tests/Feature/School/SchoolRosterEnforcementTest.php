<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use App\Services\RegisterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A school code used to be a bearer token: anyone holding it could register
 * into the school and be handed a free subscription. These pin the roster that
 * replaces it, and - just as importantly - pin that a school which has not
 * opted in still behaves exactly as it did before.
 */
class SchoolRosterEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Aisha Rahman',
            'email' => 'aisha' . uniqid() . '@example.test',
            'country_code' => '+65',
            'phone_no' => (string) fake()->unique()->numerify('8#######'),
            'password' => 'ValidPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
        ], $overrides);
    }

    private function register(array $payload): array
    {
        return (new RegisterService())->register($payload);
    }

    /** The flag-off pin: an unknown address still registers, as it does today. */
    public function test_a_school_that_has_not_opted_in_accepts_any_email(): void
    {
        $school = School::factory()->create();
        $payload = $this->payload(['school_code' => $school->school_code]);

        $result = $this->register($payload);

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertSame(
            $school->id,
            User::find($result['user']['user_id'])->school_id,
            'The parent should still be attached to the school.'
        );
    }

    public function test_an_email_on_the_roster_registers_and_claims_its_invite(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $payload = $this->payload(['school_code' => $school->school_code]);

        $invite = SchoolParentInvite::factory()
            ->for($school)
            ->forEmail($payload['email'])
            ->create();

        $result = $this->register($payload);

        $this->assertTrue($result['status'], $result['message'] ?? '');

        $invite->refresh();
        $this->assertSame('claimed', $invite->status);
        $this->assertSame($result['user']['user_id'], $invite->claimed_user_id);
    }

    public function test_an_email_not_on_the_roster_is_rejected_and_no_user_is_created(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $payload = $this->payload(['school_code' => $school->school_code]);

        $result = $this->register($payload);

        $this->assertFalse($result['status']);
        $this->assertNull($result['user']);
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    /**
     * Single claim. Even a genuinely rostered address is usable once, so a
     * forwarded invitation gets the second person nowhere.
     */
    public function test_a_rostered_email_cannot_be_claimed_twice(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $email = 'shared' . uniqid() . '@example.test';

        SchoolParentInvite::factory()->for($school)->forEmail($email)->create();

        $first = $this->register($this->payload(['school_code' => $school->school_code, 'email' => $email]));
        $this->assertTrue($first['status'], $first['message'] ?? '');

        $second = $this->register($this->payload(['school_code' => $school->school_code, 'email' => $email]));
        $this->assertFalse($second['status']);
    }

    /**
     * If "not on the roster" read differently from "already claimed", the
     * endpoint would answer whether any given address is on a school's list.
     */
    public function test_every_roster_refusal_returns_an_identical_message(): void
    {
        $school = School::factory()->enforcingRoster()->create();

        $offRoster = $this->register($this->payload(['school_code' => $school->school_code]));

        $claimedEmail = 'taken' . uniqid() . '@example.test';
        SchoolParentInvite::factory()->for($school)->forEmail($claimedEmail)
            ->claimedBy(User::factory()->parent()->create())->create();
        $alreadyClaimed = $this->register($this->payload([
            'school_code' => $school->school_code,
            'email' => $claimedEmail,
        ]));

        $revokedEmail = 'gone' . uniqid() . '@example.test';
        SchoolParentInvite::factory()->for($school)->forEmail($revokedEmail)->revoked()->create();
        $revoked = $this->register($this->payload([
            'school_code' => $school->school_code,
            'email' => $revokedEmail,
        ]));

        $this->assertFalse($offRoster['status']);
        $this->assertFalse($alreadyClaimed['status']);
        $this->assertFalse($revoked['status']);
        $this->assertSame($offRoster['message'], $alreadyClaimed['message']);
        $this->assertSame($offRoster['message'], $revoked['message']);
    }

    public function test_email_case_and_whitespace_still_match_the_roster(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $email = 'Mixed.Case' . uniqid() . '@Example.Test';

        SchoolParentInvite::factory()->for($school)->forEmail($email)->create();

        $result = $this->register($this->payload([
            'school_code' => $school->school_code,
            'email' => strtoupper($email),
        ]));

        $this->assertTrue($result['status'], $result['message'] ?? '');
    }

    public function test_self_signup_can_be_switched_off_entirely(): void
    {
        $school = School::factory()->enforcingRoster()->selfSignupDisabled()->create();
        $payload = $this->payload(['school_code' => $school->school_code]);

        SchoolParentInvite::factory()->for($school)->forEmail($payload['email'])->create();

        $result = $this->register($payload);

        $this->assertFalse($result['status'], 'A disabled school must refuse even a rostered address.');
    }

    public function test_an_invalid_school_code_still_returns_the_original_message(): void
    {
        $result = $this->register($this->payload(['school_code' => 'NOSUCHCODE']));

        $this->assertFalse($result['status']);
        $this->assertStringContainsString('school code you entered is not valid', $result['message']);
    }

    public function test_registration_without_a_school_code_is_untouched(): void
    {
        $result = $this->register($this->payload());

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertNull(User::find($result['user']['user_id'])->school_id);
    }
}
