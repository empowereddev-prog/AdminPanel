<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class BatteryLowCheckCommand extends Command
{
    protected $signature = 'app:battery-low-check';
    protected $description = 'Check all child users and send low battery notification if <20% (with device token)';

    public function handle(): void
    {
        // Sirf child + active users ke liye jinke paas device token ho
        $users = User::where('user_type', 'child')
            ->where('status', 'active')
            ->whereHas('deviceTokens', fn($q) => $q->whereNotNull('token'))
            ->cursor();

        foreach ($users as $user) {
            if ($user->battery_points <= 20) {
                //Template se content lao
                $content = getNotificationContent('battery_low', [
                    'battery_points' => $user->battery_points,
                ]);

                $notification_type = 'battery_low';
                $user_type = "user";

                $userData = [
                    // 'title' => "Battery low",
                    'type'  => $notification_type,
                ];
                
                sendNotificationSender(
                    $user->id,
                    $content['title'],   
                    $content['body'],    
                    $notification_type,
                    $userData,
                    $user_type
                );
            }
        }
        $this->info("Battery low check completed successfully (sent every time battery <20%).");
    }
}
