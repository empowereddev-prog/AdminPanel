<?php

namespace App\Jobs;

use App\Models\BatterySetting;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\ChildMood;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class DailyMoodPenaltyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $date = Carbon::today();

        $userIds = UserLogin::whereDate('logged_in_at', $date)->distinct()->pluck('user_id');

        foreach ($userIds as $uid) {
            $user = User::find($uid);
            if (!$user) continue;

            $hasMood = ChildMood::where('child_id', $uid)->whereDate('date', $date)->exists();
            if (!$hasMood) {
                $mood_battery_value = BatterySetting::where('option_key', 'mood_battery_percentage_negative')->first();
                $points = $mood_battery_value ? $mood_battery_value->option_value : 10;
                battery_debit_once_per_day($user, $points, 'mood_penalty', ['date' => $date->toDateString()], $date);
            }
        }
    }
}
