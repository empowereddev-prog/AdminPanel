<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolParentInvite>
 */
class SchoolParentInviteFactory extends Factory
{
    protected $model = SchoolParentInvite::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            // Normalised on the way in, exactly as every production write path
            // must - the unique index does no case folding of its own.
            'email' => SchoolParentInvite::normaliseEmail($this->faker->unique()->safeEmail()),
            'name' => $this->faker->name(),
            'status' => 'invited',
            'claimed_user_id' => null,
            'invited_at' => now(),
        ];
    }

    public function forEmail(string $email): static
    {
        return $this->state(fn () => ['email' => SchoolParentInvite::normaliseEmail($email)]);
    }

    public function claimedBy(User $user): static
    {
        return $this->state(fn () => [
            'status' => 'claimed',
            'claimed_user_id' => $user->id,
            'claimed_at' => now(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['status' => 'revoked', 'claimed_user_id' => null]);
    }
}
