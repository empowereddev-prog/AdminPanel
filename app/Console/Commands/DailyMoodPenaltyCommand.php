<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\ChildMood;
use App\Models\BatterySetting;
use Illuminate\Support\Carbon;

class DailyMoodPenaltyCommand extends Command
{
    protected $signature = 'app:daily-mood-penalty';
    protected $description = 'Apply daily mood penalty if child user did not log mood today';

    public function handle(): void
    {
        $date = Carbon::today();

        //  Aaj login kiye users
        $userIds = UserLogin::whereDate('logged_in_at', $date)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $uid) {
            //  Sirf child users
            $user = User::where('id', $uid)
                ->where('user_type', 'child')
                ->first();

            if (!$user) continue;

            //  Mood check karo
            $hasMood = ChildMood::where('child_id', $uid)
                ->where('date', $date->toDateString())
                ->exists();

            if (!$hasMood) {
                //  Battery setting se penalty points lao
                $mood_battery_value = BatterySetting::where('option_key', 'mood_battery_percentage_negative')->first();
                $points = $mood_battery_value ? $mood_battery_value->option_value : 10;

                //  Penalty lagao (ek din me ek hi baar)
                battery_debit_once_per_day(
                    $user,
                    $points,
                    'mood_penalty',
                    ['date' => $date->toDateString()],
                    $date
                );
            }
        }

        $this->info("Daily Mood Penalty applied successfully for " . count($userIds) . " users.");
    }
}
