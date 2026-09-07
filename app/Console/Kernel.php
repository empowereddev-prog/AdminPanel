<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    protected $commands = [

        commands\UpdateUserMood::class,
    ];
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('app:update-user-mood')->daily();
        $schedule->command('app:send-scheduled-notifications')->everyMinute();
        // $schedule->command('app:check-negative-moods')->daily(); // or hourly

        $schedule->command('app:daily-mood-penalty')->dailyAt('09:00');
        $schedule->command('app:weekly-login-bonus-penalty')->dailyAt('09:00');
        $schedule->command('app:battery-low-check')->dailyAt('09:00');
        // $schedule->command('subscriptions:expire')->daily();

        $schedule->command('audit:prune')->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
