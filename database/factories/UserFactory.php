<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * definition() above sets none of user_role_id, user_type or status, all of
     * which the application requires, so anything school-related has to come
     * through one of these states.
     */
    public function parent(): static
    {
        return $this->state(fn () => [
            'user_role_id' => 3,
            'user_type' => 'parent',
            'status' => 'active',
            'language' => 'english',
            'is_mobile_verified' => 'yes',
        ]);
    }

    /**
     * A child is a users row with parent_id set and school_id deliberately
     * NULL - that null is the whole reason the seat count has to be derived
     * through the parent rather than read off the child.
     */
    public function child(\App\Models\User $parent): static
    {
        return $this->state(fn () => [
            'user_role_id' => 4,
            'user_type' => 'child',
            'status' => 'active',
            'parent_id' => $parent->id,
            'school_id' => null,
            'username' => fake()->unique()->userName(),
            'battery_points' => 100,
        ]);
    }

    public function inSchool(\App\Models\School $school): static
    {
        return $this->state(fn () => ['school_id' => $school->id]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => [
            'user_role_id' => 5,
            'user_type' => 'teacher',
            'status' => 'active',
            'username' => fake()->unique()->userName(),
        ]);
    }
}
