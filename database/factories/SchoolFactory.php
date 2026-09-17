<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company() . ' School',
            'email' => $this->faker->unique()->safeEmail(),
            'school_code' => strtoupper(Str::random(8)),
            // schools.user_id is the panel admin who created the row, not the
            // school itself - there is no school-facing login in this system.
            'user_id' => User::factory(),
            'status' => 'active',
            'max_limit' => 100,
            'subscription_type' => 'monthly',
            // Every control defaults to its migration default, i.e. to today's
            // behaviour. Tests opt in explicitly through the states below.
            'child_seat_limit' => null,
            'per_parent_child_limit' => null,
            'enforce_parent_roster' => 'no',
            'self_signup_enabled' => 'yes',
        ];
    }

    public function enforcingRoster(): static
    {
        return $this->state(fn () => ['enforce_parent_roster' => 'yes']);
    }

    public function selfSignupDisabled(): static
    {
        return $this->state(fn () => ['self_signup_enabled' => 'no']);
    }

    public function withChildSeats(int $limit): static
    {
        return $this->state(fn () => ['child_seat_limit' => $limit]);
    }

    public function withPerParentLimit(int $limit): static
    {
        return $this->state(fn () => ['per_parent_child_limit' => $limit]);
    }
}
