<?php

namespace Tests\Feature\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use App\Services\School\SchoolSeatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * update-parent-profile is the second door into a school: it let any registered
 * parent attach themselves to any school by typing its code, with no roster
 * check and no cap.
 */
class UpdateParentProfileSchoolTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    private function actingAsParent(array $attributes = []): User
    {
        $parent = User::factory()->parent()->create($attributes);
        Passport::actingAs($parent, [], 'api');

        return $parent;
    }

    private function update(array $payload)
    {
        return $this->postJson('/api/update-parent-profile', $payload + ['language' => 'english']);
    }

    public function test_an_unattached_parent_on_the_roster_can_join(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $parent = $this->actingAsParent();
        SchoolParentInvite::factory()->for($school)->forEmail($parent->email)->create();

        $this->update(['school_code' => $school->school_code])->assertOk()->assertJson(['status' => true]);

        $this->assertSame($school->id, $parent->fresh()->school_id);
        $this->assertSame('claimed', SchoolParentInvite::where('school_id', $school->id)->first()->status);
    }

    public function test_an_off_roster_parent_cannot_join(): void
    {
        $school = School::factory()->enforcingRoster()->create();
        $parent = $this->actingAsParent();

        $this->update(['school_code' => $school->school_code])->assertStatus(422)->assertJson(['status' => false]);

        $this->assertNull($parent->fresh()->school_id);
    }

    /** A parent cannot carry their school-granted access from one school to another. */
    public function test_a_parent_cannot_hop_from_one_school_to_another(): void
    {
        $current = School::factory()->create();
        $other = School::factory()->create();
        $parent = $this->actingAsParent(['school_id' => $current->id]);

        $response = $this->update(['school_code' => $other->school_code]);

        $response->assertStatus(422)->assertJson(['status' => false]);
        $this->assertStringContainsString('already linked', $response->json('message'));
        $this->assertSame($current->id, $parent->fresh()->school_id);
    }

    /** Flag-off pin: a school that has not opted in still accepts anyone. */
    public function test_a_school_without_enforcement_still_accepts_any_parent(): void
    {
        $school = School::factory()->create();
        $parent = $this->actingAsParent();

        $this->update(['school_code' => $school->school_code])->assertOk();

        $this->assertSame($school->id, $parent->fresh()->school_id);
    }

    public function test_an_invalid_code_still_returns_422(): void
    {
        $this->actingAsParent();

        $this->update(['school_code' => 'NOPE'])->assertStatus(422);
    }

    /**
     * The name was written unconditionally, so any call that omitted the field
     * blanked the parent's name.
     */
    public function test_omitting_name_no_longer_wipes_it(): void
    {
        $school = School::factory()->create();
        $parent = $this->actingAsParent(['name' => 'Original Name']);

        $this->update(['school_code' => $school->school_code])->assertOk();

        $this->assertSame('Original Name', $parent->fresh()->name);
    }

    public function test_name_is_still_updated_when_supplied(): void
    {
        $parent = $this->actingAsParent(['name' => 'Original Name']);

        $this->update(['name' => 'Changed Name'])->assertOk();

        $this->assertSame('Changed Name', $parent->fresh()->name);
    }

    /**
     * The seat check must actually take the row lock, not merely read the
     * count - otherwise two concurrent add-child calls can both see one place
     * left. This asserts the SELECT ... FOR UPDATE is issued against schools.
     *
     * NOTE: this proves the lock is requested. Genuine multi-process contention
     * cannot be exercised from a single-process PHPUnit run and should be
     * checked once on staging.
     */
    public function test_the_seat_check_locks_the_school_row(): void
    {
        $school = School::factory()->withChildSeats(5)->create();
        $parent = User::factory()->parent()->inSchool($school)->create();

        $statements = [];
        DB::listen(function ($query) use (&$statements) {
            $statements[] = $query->sql;
        });

        DB::transaction(fn () => (new SchoolSeatService())->checkChildSeat($parent));

        $locking = array_filter($statements, fn ($sql) => str_contains(strtolower($sql), 'for update')
            && str_contains(strtolower($sql), 'schools'));

        $this->assertNotEmpty($locking, 'checkChildSeat must SELECT ... FOR UPDATE the schools row.');
    }
}
