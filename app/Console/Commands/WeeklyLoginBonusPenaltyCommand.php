<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApiHitLog;
use App\Models\BatterySetting;
use App\Models\User;
use App\Models\BatteryEvent;
use Carbon\Carbon;

class WeeklyLoginBonusPenaltyCommand extends Command
{
    protected $signature = 'app:weekly-login-bonus-penalty';
    protected $description = 'Apply weekly login bonus or penalty (one time per week)';

    public function handle(): void
    {
        $start = Carbon::now()->startOfWeek();
        $end   = Carbon::now()->endOfWeek();

        foreach (User::where('user_type', 'child')->cursor() as $user) {

            // ❌ Already processed this week? skip
            $alreadyProcessed = BatteryEvent::where('user_id', $user->id)
                ->whereIn('reason', ['profile_bonus', 'profile_penalty'])
                ->whereBetween('effective_date', [$start, $end])
                ->exists();

            if ($alreadyProcessed) {
                continue;
            }

            // ✅ Weekly hit count
            $hitCount = ApiHitLog::where('user_id', $user->id)
                ->where('api_name', 'getProfile')
                ->whereBetween('created_at', [$start, $end])
                ->count();

            if ($hitCount >= 2) {
                // ✅ BONUS
                $points = BatterySetting::where('option_key', 'login_battery_percentage')
                    ->value('option_value') ?? 10;

                BatteryEvent::create([
                    'user_id' => $user->id,
                    'direction' => 'credit',
                    'points' => $points,
                    'reason' => 'profile_bonus',
                    'effective_date' => now(),
                    'meta' => json_encode(['count' => $hitCount])
                ]);
            } else {
                // ❌ PENALTY
                $points = BatterySetting::where('option_key', 'login_battery_percentage_negative')
                    ->value('option_value') ?? 10;

                BatteryEvent::create([
                    'user_id' => $user->id,
                    'direction' => 'debit',
                    'points' => $points,
                    'reason' => 'profile_penalty',
                    'effective_date' => now(),
                    'meta' => json_encode(['count' => $hitCount])
                ]);
            }
        }

        $this->info('✅ Weekly login bonus/penalty applied successfully.');
    }
}
