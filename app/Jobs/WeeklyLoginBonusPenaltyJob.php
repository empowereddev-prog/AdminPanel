<?php

namespace App\Jobs;

use App\Models\ApiHitLog;
use App\Models\BatterySetting;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class WeeklyLoginBonusPenaltyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays(6)->startOfDay();

        foreach (User::cursor() as $user) {
            $count = ApiHitLog::where('user_id', $user->id)
                ->where('api_name', 'getProfile') 
                ->whereBetween('created_at', [$start, $end])
                ->count();

            if ($count >= 2) {
                $loginBatterySetting = BatterySetting::where('option_key', 'login_battery_percentage')->first();
                $points = $loginBatterySetting ? $loginBatterySetting->option_value : 10;
                battery_adjust($user, +$points, 'profile_bonus', ['count' => $count], $end);
            } else {
                $loginBatterySetting = BatterySetting::where('option_key', 'login_battery_percentage_negative')->first();
                $points = $loginBatterySetting ? $loginBatterySetting->option_value : 10;
                battery_adjust($user, -$points, 'profile_penalty', ['count' => $count], $end);
            }
        }
    }
}
