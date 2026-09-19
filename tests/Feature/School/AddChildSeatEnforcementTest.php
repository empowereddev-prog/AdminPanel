<?php

namespace Tests\Feature\School;

use App\Models\BatteryEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The endpoint-level half of the seat cap. The service tests cover the counting;
 * these cover the envelope the shipped app actually receives, and that a refusal
 * leaves no partial rows behind.
 */
class AddChildSeatEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    private function actingAsParent(?School $school): User
    {
        $state = User::factory()->parent();
        $parent = $school ? $state->inSchool($school)->create() : $state->create();

        Passport::actingAs($parent, [], 'api');

        return $parent;
    }

    private function addChild(array $overrides = [])
    {
        return $this->postJson('/api/add-child', array_merge([
            'name' => 'Small Child',
            'dob' => '2019-04',
            'username' => 'child' . uniqid(),
            'password' => 'ChildPass1@',
            'language' => 'english',
        ], $overrides));
    }

    public function test_a_parent_under_the_cap_can_add_a_child(): void
    {
        $school = School::factory()->withChildSeats(2)->create();
        $this->actingAsParent($school);

        $this->addChild()->assertStatus(201)->assertJson(['status' => true]);
    }

    /**
     * v1 answers refusals with HTTP 201 and status:false - every other
     * validation failure on this endpoint already does, and the shipped app
     * reads it that way. A thrown exception would have surfaced as a 500.
     */
    public function test_a_full_school_is_refused_with_the_v1_envelope(): void
    {
        $school = School::factory()->withChildSeats(1)->create();
        $parent = $this->actingAsParent($school);
        User::factory()->child($parent)->create();

        $response = $this->addChild();

        $response->assertStatus(201)->assertJson(['status' => false]);
        $this->assertStringContainsString('school', strtolower($response->json('message')));
    }

    /** A refusal must leave no child row and no orphaned battery ledger entry. */
    public function test_a_refusal_writes_nothing_at_all(): void
    {
        $school = School::factory()->withChildSeats(1)->create();
        $parent = $this->actingAsParent($school);
        User::factory()->child($parent)->create();

        $childrenBefore = User::where('parent_id', $parent->id)->count();
        $eventsBefore = BatteryEvent::count();

        $this->addChild(['username' => 'rejected' . uniqid()]);

        $this->assertSame($childrenBefore, User::where('parent_id', $parent->id)->count());
        $this->assertSame($eventsBefore, BatteryEvent::count(), 'A BatteryEvent was written for a child that does not exist.');
    }

    public function test_the_per_parent_cap_is_enforced_at_the_endpoint(): void
    {
        $school = School::factory()->withChildSeats(50)->withPerParentLimit(1)->create();
        $parent = $this->actingAsParent($school);
        User::factory()->child($parent)->create();

        $response = $this->addChild();

        $response->assertStatus(201)->assertJson(['status' => false]);
        $this->assertStringContainsString('per parent', strtolower($response->json('message')));
    }

    /** The flag-off pin: a school with no caps set behaves as it always has. */
    public function test_a_school_without_caps_is_unaffected(): void
    {
        $school = School::factory()->create();
        $parent = $this->actingAsParent($school);
        User::factory()->count(4)->child($parent)->create();

        $this->addChild()->assertStatus(201)->assertJson(['status' => true]);
    }

    public function test_a_consumer_parent_is_unaffected(): void
    {
        $parent = $this->actingAsParent(null);
        User::factory()->count(4)->child($parent)->create();

        $this->addChild()->assertStatus(201)->assertJson(['status' => true]);
    }

    /** The additive block the app reads to disable "Add child" before calling. */
    public function test_get_profile_exposes_seat_usage_to_a_school_parent(): void
    {
        $school = School::factory()->withChildSeats(10)->withPerParentLimit(3)->create();
        $parent = $this->actingAsParent($school);
        User::factory()->count(2)->child($parent)->create();

        $seats = $this->getJson('/api/get-profile?language=english')
            ->assertStatus(200)
            ->json('data.seats');

        $this->assertSame(10, $seats['child_seat_limit']);
        $this->assertSame(2, $seats['child_seats_used']);
        $this->assertSame(8, $seats['child_seats_remaining']);
        $this->assertSame(3, $seats['per_parent_child_limit']);
        $this->assertSame(2, $seats['my_children']);
    }

    public function test_get_profile_reports_null_seats_for_a_consumer_parent(): void
    {
        $this->actingAsParent(null);

        $this->getJson('/api/get-profile?language=english')
            ->assertStatus(200)
            ->assertJsonPath('data.seats', null);
    }
}
