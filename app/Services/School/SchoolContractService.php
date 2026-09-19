<?php

namespace App\Services\School;

use App\Models\School;
use App\Models\SchoolSubscription;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

/**
 * The school's commercial contract (Payment History) is a different record
 * from the per-parent entitlement grants on `subscriptions`. Mixing them is
 * what made every imported parent look like a paid purchase.
 */
class SchoolContractService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function termDates(?string $type, $from = null): array
    {
        $start = Carbon::parse($from ?? now())->startOfDay();
        $end = match ($type) {
            'quarterly' => $start->copy()->addMonths(3),
            'yearly' => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };

        return [$start, $end];
    }

    public function recordOnCreate(School $school): SchoolSubscription
    {
        [$start, $end] = $this->termDates($school->subscription_type);

        return SchoolSubscription::create($this->attributes($school, $start, $end, 'successful'));
    }

    /**
     * Type or price change: close the current successful row and open a new
     * one so Payment History keeps the previous amount. Limit-only edits
     * update the snapshot on the current row.
     */
    public function syncOnUpdate(School $school, ?string $previousType, $previousPrice): SchoolSubscription
    {
        $current = SchoolSubscription::where('school_id', $school->id)
            ->where('status', 'successful')
            ->latest('id')
            ->first();

        $typeChanged = (string) $previousType !== (string) $school->subscription_type;
        $priceChanged = $this->money((float) $previousPrice) !== $this->money((float) $school->price);

        if ($current && !$typeChanged && !$priceChanged) {
            $current->update([
                'parent_limit' => $school->max_limit,
                'child_seat_limit' => $school->child_seat_limit,
            ]);

            return $current;
        }

        if ($current) {
            $current->update(['status' => 'expired']);
        }

        [$start, $end] = $this->termDates($school->subscription_type);

        return SchoolSubscription::create($this->attributes($school, $start, $end, 'successful'));
    }

    /**
     * App entitlement only. Price is 0 so a raw query on `subscriptions`
     * cannot be mistaken for school revenue.
     */
    public function grantParentEntitlement(School $school, User $user): void
    {
        $type = $school->subscription_type ?: 'monthly';
        [$start, $end] = $this->termDates($type);

        // Only a *live* entitlement should suppress the grant. Matching any
        // historical row meant a parent whose own plan lapsed months ago got
        // nothing when their school imported them - a seat the school paid for
        // and the parent never received.
        $exists = Subscription::where('user_id', $user->id)
            ->where('user_type', 'parent')
            ->where('end_date', '>=', now()->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        Subscription::create([
            'user_id' => $user->id,
            'subscription_type_id' => match ($type) {
                'quarterly' => 'com.empowered.quarterly',
                'yearly' => 'com.empowered.yearly',
                default => 'com.empowered.monthly',
            },
            'user_type' => 'parent',
            'subscription_type' => $type,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'currency' => 'SGD',
            'status' => 'Successful',
            // The durable marker. Price stays 0 as well so a raw query written
            // against the old assumption still behaves.
            'source' => 'school_grant',
            'price' => '0',
        ]);
    }

    /**
     * Idempotent: schools with no contract row get one at stored price or 0.
     *
     * @return array{created:int,skipped:int}
     */
    public function backfillMissing(): array
    {
        $created = 0;
        $skipped = 0;

        foreach (School::orderBy('id')->get() as $school) {
            if (SchoolSubscription::where('school_id', $school->id)->exists()) {
                $skipped++;
                continue;
            }

            [$start, $end] = $this->termDates($school->subscription_type, $school->created_at);
            SchoolSubscription::create($this->attributes($school, $start, $end, 'successful'));
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function attributes(School $school, Carbon $start, Carbon $end, string $status): array
    {
        return [
            'school_id' => $school->id,
            'subscription_type' => $school->subscription_type ?: 'monthly',
            'price' => $school->price ?? 0,
            'currency' => 'SGD',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => $status,
            'parent_limit' => $school->max_limit,
            'child_seat_limit' => $school->child_seat_limit,
        ];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
