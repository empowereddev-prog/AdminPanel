<?php

namespace Tests\Feature\Api;

use App\Models\BatteryEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The users table was missing every column the child feature needs, so
 * addChild failed outright on a database built from this repo. These tests pin
 * the aligned schema and the endpoint that depends on it.
 */
class SchemaAlignmentTest extends TestCase
{
    use DatabaseTransactions;

    private function parent(): User
    {
        return User::create([
            'name' => 'Parent',
            'email' => 'p' . uniqid() . '@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'parent',
            'user_role_id' => 3,
            'status' => 'active',
            'language' => 'english',
        ]);
    }

    public function test_users_table_has_every_column_the_code_writes(): void
    {
        foreach ([
            'username', 'dob', 'loyalty_points', 'avtar_image',
            'is_first_login', 'is_avatar_primary', 'is_mood_updated',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "users.{$column} is written by the application but missing."
            );
        }
    }

    public function test_user_type_accepts_child(): void
    {
        $child = User::create([
            'name' => 'Kid',
            'email' => 'k' . uniqid() . '@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'child',
            'user_role_id' => 4,
            'status' => 'active',
        ]);

        $this->assertSame('child', $child->fresh()->user_type);
    }

    /** The app writes 'yes'/'no' here; the column used to be enum('0','1'). */
    public function test_is_mobile_verified_accepts_the_values_the_code_writes(): void
    {
        $user = $this->parent();

        $user->update(['is_mobile_verified' => 'yes']);

        $this->assertSame('yes', $user->fresh()->is_mobile_verified);
        $this->assertTrue(
            User::where('id', $user->id)->where('is_mobile_verified', 'yes')->exists(),
            "where('is_mobile_verified','yes') must match, as the admin queries assume."
        );
    }

    /** loyalty_points must serialise as a number, like its sibling battery_points. */
    public function test_loyalty_points_serialises_as_a_number(): void
    {
        $user = $this->parent();
        $user->increment('loyalty_points', 25.5);

        $payload = $user->fresh()->toArray();

        $this->assertIsFloat($payload['loyalty_points']);
        $this->assertSame(25.5, $payload['loyalty_points']);
    }

    /** The end-to-end proof: this endpoint could not run at all before. */
    public function test_add_child_creates_the_child_and_its_battery_ledger(): void
    {
        $parent = $this->parent();
        Passport::actingAs($parent, [], 'api');

        $username = 'kid_' . uniqid();

        $response = $this->postJson('/api/add-child', [
            'name' => 'Test Child',
            'dob' => '2015-04',
            'username' => $username,
            'password' => 'ChildPass1@',
            'language' => 'english',
        ]);

        $response->assertStatus(201)->assertJsonPath('status', true);

        $child = User::where('username', $username)->first();

        $this->assertNotNull($child, 'Child user row must exist.');
        $this->assertSame($parent->id, (int) $child->parent_id);
        $this->assertSame('child', $child->user_type);
        $this->assertSame('2015-04', $child->dob);
        $this->assertSame(100, (int) $child->battery_points);

        // The opening ledger entry must commit with the child, since getProfile
        // derives the battery percentage from it.
        $this->assertTrue(
            BatteryEvent::where('user_id', $child->id)->where('direction', 'credit')->exists(),
            'Opening battery ledger entry must exist.'
        );
    }

    /** addChild and the age gate agree on the stored dob format. */
    public function test_stored_dob_parses_with_both_formats_the_code_uses(): void
    {
        $parent = $this->parent();
        Passport::actingAs($parent, [], 'api');

        $username = 'kid_' . uniqid();

        $this->postJson('/api/add-child', [
            'name' => 'Test Child',
            'dob' => '2015-04',
            'username' => $username,
            'password' => 'ChildPass1@',
        ])->assertStatus(201);

        $dob = User::where('username', $username)->value('dob');

        // The age gates parse this column two different ways; both must work
        // on what addChild stores, or the parent dashboard 500s.
        $viaYearMonth = \Carbon\Carbon::createFromFormat('Y-m', $dob);
        $viaDatePad   = \Carbon\Carbon::createFromFormat('Y-m-d', $dob . '-01');

        $this->assertSame(2015, $viaYearMonth->year);
        $this->assertSame(4, $viaYearMonth->month);
        $this->assertSame(2015, $viaDatePad->year);
        $this->assertSame(4, $viaDatePad->month);
    }
}
