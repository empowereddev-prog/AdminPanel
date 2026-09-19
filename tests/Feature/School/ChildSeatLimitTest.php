<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolSeatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The school pays for a number of CHILD registrations, but children are users
 * rows with school_id NULL, so max_limit never counted one and addChild had no
 * cap at all. These pin the derived count and both caps.
 */
class ChildSeatLimitTest extends TestCase
{
    use DatabaseTransactions;

    private function seats(): SchoolSeatService
    {
        return new SchoolSeatService();
    }

    private function parentIn(?School $school): User
    {
        $state = User::factory()->parent();

        return $school ? $state->inSchool($school)->create() : $state->create();
    }

    private function childrenFor(User $parent, int $count): void
    {
        User::factory()->count($count)->child($parent)->create();
    }

    public function test_a_consumer_parent_is_never_capped(): void
    {
        $parent = $this->parentIn(null);
        $this->childrenFor($parent, 5);

        $result = $this->seats()->checkChildSeat($parent);

        $this->assertTrue($result['allowed']);
        $this->assertSame('no_school', $result['reason'], 'A parent with no school must skip the gate entirely.');
    }

    public function test_a_school_with_no_limits_set_behaves_exactly_as_before(): void
    {
        $school = School::factory()->create();
        $parent = $this->parentIn($school);
        $this->childrenFor($parent, 10);

        $result = $this->seats()->checkChildSeat($parent);

        $this->assertTrue($result['allowed']);
        $this->assertSame('no_limit', $result['reason']);
    }

    /**
     * The case a naive implementation misses. Seats are school-wide, so two
     * children belonging to two DIFFERENT parents still consume two of the
     * school's places - a per-parent count, or a count keyed on the child's own
     * (always null) school_id, would let the third through.
     */
    public function test_seats_are_counted_across_all_parents_of_the_school(): void
    {
        $school = School::factory()->withChildSeats(2)->create();
        $parentA = $this->parentIn($school);
        $parentB = $this->parentIn($school);

        $this->childrenFor($parentA, 1);
        $this->childrenFor($parentB, 1);

        $this->assertSame(2, $this->seats()->childCountForSchool($school->id));

        $result = $this->seats()->checkChildSeat($parentB);

        $this->assertFalse($result['allowed']);
        $this->assertSame('school_seats_full', $result['reason']);
    }

    public function test_children_of_another_school_do_not_consume_this_schools_seats(): void
    {
        $school = School::factory()->withChildSeats(2)->create();
        $other = School::factory()->create();

        $this->childrenFor($this->parentIn($other), 4);

        $this->assertSame(0, $this->seats()->childCountForSchool($school->id));
        $this->assertTrue($this->seats()->checkChildSeat($this->parentIn($school))['allowed']);
    }

    public function test_deleting_a_child_frees_a_seat(): void
    {
        $school = School::factory()->withChildSeats(1)->create();
        $parent = $this->parentIn($school);
        $child = User::factory()->child($parent)->create();

        $this->assertFalse($this->seats()->checkChildSeat($parent)['allowed']);

        $child->delete(); // soft delete

        $this->assertSame(0, $this->seats()->childCountForSchool($school->id));
        $this->assertTrue($this->seats()->checkChildSeat($parent)['allowed']);
    }

    public function test_per_parent_limit_blocks_one_parent_while_the_school_still_has_room(): void
    {
        $school = School::factory()->withChildSeats(50)->withPerParentLimit(3)->create();
        $greedy = $this->parentIn($school);
        $other = $this->parentIn($school);

        $this->childrenFor($greedy, 3);

        $blocked = $this->seats()->checkChildSeat($greedy);
        $this->assertFalse($blocked['allowed']);
        $this->assertSame('parent_seats_full', $blocked['reason']);

        // The school is nowhere near its own cap, so a different parent is fine.
        $this->assertTrue($this->seats()->checkChildSeat($other)['allowed']);
    }

    /**
     * The two refusals must read differently: one is fixed by the parent
     * removing a child, the other only by the school buying more places.
     */
    public function test_the_two_refusals_carry_different_messages(): void
    {
        $perParent = School::factory()->withPerParentLimit(1)->create();
        $parentA = $this->parentIn($perParent);
        $this->childrenFor($parentA, 1);

        $schoolWide = School::factory()->withChildSeats(1)->create();
        $parentB = $this->parentIn($schoolWide);
        $this->childrenFor($parentB, 1);

        $this->assertNotSame(
            $this->seats()->checkChildSeat($parentA)['message'],
            $this->seats()->checkChildSeat($parentB)['message']
        );
    }

    public function test_summary_reports_usage_and_leaves_unlimited_as_null(): void
    {
        $school = School::factory()->create();
        $parent = $this->parentIn($school);
        $this->childrenFor($parent, 2);

        $summary = $this->seats()->summaryForSchool($school);

        $this->assertSame(2, $summary['child_seats_used']);
        $this->assertNull($summary['child_seat_limit']);
        $this->assertNull($summary['child_seats_remaining']);
        $this->assertSame(1, $summary['parents_used'], 'Only role 3 counts as a parent.');
    }

    /**
     * Teachers carry school_id too. If they leaked into the child count the
     * school would lose places it never used.
     */
    public function test_teachers_are_not_counted_as_children_or_parents(): void
    {
        $school = School::factory()->create();
        User::factory()->teacher()->inSchool($school)->create();

        $summary = $this->seats()->summaryForSchool($school);

        $this->assertSame(0, $summary['child_seats_used']);
        $this->assertSame(0, $summary['parents_used']);
    }
}
