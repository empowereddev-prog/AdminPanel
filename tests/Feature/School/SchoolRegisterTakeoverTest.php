<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use App\Services\RegisterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The roster keys on email. RegisterService's existing-account lookup keys on
 * "email OR phone_no". That mismatch is a way through the roster:
 *
 *   1. Attacker is legitimately on the school's roster as a@school.test.
 *   2. Attacker registers with a@school.test AND the victim's phone number.
 *   3. The lookup matches the VICTIM by phone; the roster check passes on the
 *      attacker's own email.
 *   4. The unverified-user branch overwrites the victim's row wholesale -
 *      password and school_id included.
 *
 * These pin the identity guard that closes it. Without that guard the roster is
 * bypassable on the day it ships.
 */
class SchoolRegisterTakeoverTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    private function victim(string $phone): User
    {
        return User::factory()->parent()->create([
            'email' => 'victim' . uniqid() . '@example.test',
            'phone_no' => $phone,
            'country_code' => '+65',
            'password' => Hash::make('VictimPass1@'),
            // Unverified is the vulnerable state: the branch that overwrites.
            'is_mobile_verified' => 'no',
        ]);
    }

    public function test_a_rostered_attacker_cannot_hijack_an_account_via_its_phone_number(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $phone = '81234567';
        $victim = $this->victim($phone);

        $attackerEmail = 'attacker' . uniqid() . '@example.test';
        SchoolParentInvite::factory()->for($school)->forEmail($attackerEmail)->create();

        $result = (new RegisterService())->register([
            'name' => 'Attacker',
            'email' => $attackerEmail,          // genuinely on the roster
            'country_code' => '+65',
            'phone_no' => $phone,               // but the victim's phone
            'password' => 'AttackerPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
            'school_code' => $school->school_code,
        ]);

        $this->assertFalse($result['status'], 'The takeover attempt must be refused.');

        $victim->refresh();
        $this->assertTrue(
            Hash::check('VictimPass1@', $victim->password),
            "The victim's password was overwritten - the account was taken over."
        );
        $this->assertNull($victim->school_id, "The victim was pulled into the attacker's school.");
        $this->assertNotSame($attackerEmail, $victim->email);
    }

    /** The refusal must reuse the existing copy, not introduce a new oracle. */
    public function test_the_refusal_reuses_the_existing_already_registered_message(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $phone = '82345678';
        $this->victim($phone);

        $attackerEmail = 'attacker' . uniqid() . '@example.test';
        SchoolParentInvite::factory()->for($school)->forEmail($attackerEmail)->create();

        $result = (new RegisterService())->register([
            'name' => 'Attacker',
            'email' => $attackerEmail,
            'country_code' => '+65',
            'phone_no' => $phone,
            'password' => 'AttackerPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
            'school_code' => $school->school_code,
        ]);

        $this->assertSame('The phone number or email is already registered.', $result['message']);
    }

    /**
     * The guard is scoped to school registrations on purpose, so the ordinary
     * consumer path keeps resuming an abandoned unverified signup as it does
     * today.
     */
    public function test_a_non_school_signup_can_still_resume_its_own_unverified_account(): void
    {
        $phone = '83456789';
        $victim = $this->victim($phone);

        $result = (new RegisterService())->register([
            'name' => 'Same Person',
            'email' => $victim->email,
            'country_code' => '+65',
            'phone_no' => $phone,
            'password' => 'NewPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
        ]);

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertSame($victim->id, $result['user']['user_id']);
    }

    /**
     * Pins that hoisting the transaction around both branches did not change
     * who receives a free subscription. Issuance belongs to the new-user branch
     * only; a resumed unverified account must not silently acquire one.
     */
    public function test_resuming_an_unverified_account_does_not_grant_a_new_subscription(): void
    {
        $school = School::factory()->create();
        $phone = '85678901';
        $victim = $this->victim($phone);

        $this->assertSame(0, \App\Models\Subscription::where('user_id', $victim->id)->count());

        $result = (new RegisterService())->register([
            'name' => 'Same Person',
            'email' => $victim->email,
            'country_code' => '+65',
            'phone_no' => $phone,
            'password' => 'NewPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
            'school_code' => $school->school_code,
        ]);

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertSame(
            0,
            \App\Models\Subscription::where('user_id', $victim->id)->count(),
            'Resuming an unverified signup must not start granting a subscription it never granted before.'
        );
    }

    /** Same person, same email, joining their own school: still allowed. */
    public function test_a_rostered_parent_can_resume_their_own_unverified_account(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $phone = '84567890';
        $victim = $this->victim($phone);

        SchoolParentInvite::factory()->for($school)->forEmail($victim->email)->create();

        $result = (new RegisterService())->register([
            'name' => 'Same Person',
            'email' => $victim->email,
            'country_code' => '+65',
            'phone_no' => $phone,
            'password' => 'NewPass1@',
            'user_type' => 'parent',
            'terms_n_conditions_accepted' => 'yes',
            'language' => 'english',
            'school_code' => $school->school_code,
        ]);

        $this->assertTrue($result['status'], $result['message'] ?? '');
        $this->assertSame($school->id, $victim->fresh()->school_id);
    }
}
