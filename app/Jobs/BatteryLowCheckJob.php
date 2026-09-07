<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\AppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BatteryLowCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        foreach (User::cursor() as $user) {
            if ($user->battery_points < 20) {
                $already = AppNotification::where('user_id', $user->id)
                    ->where('type', 'battery_low')
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$already) {
                    sendNotificationSender(
                        $user->id,
                        'Battery low',
                        "Your battery is at {$user->battery_points}%. Log a mood or complete quizzes to top up.",
                        'battery_low',
                        ['battery' => $user->battery_points],
                        'user'
                    );
                }
            }
        }
    }
}
