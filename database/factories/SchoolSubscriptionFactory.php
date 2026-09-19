<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSubscription>
 */
class SchoolSubscriptionFactory extends Factory
{
    protected $model = SchoolSubscription::class;

    public function definition(): array
    {
        $start = now()->startOfDay();

        return [
            'school_id' => School::factory(),
            'subscription_type' => 'monthly',
            'price' => 13.49,
            'currency' => 'SGD',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addMonth()->toDateString(),
            'status' => 'successful',
            'parent_limit' => 100,
            'child_seat_limit' => 50,
        ];
    }
}
