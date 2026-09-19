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

        // No queue:work here. The queue is drained by a persistent worker -
        // scripts/deploy/ec2-queue-worker.service - rather than a once-a-minute
        // scheduled run, because the scheduled version only fired if someone had
        // installed the `schedule:run` cron by hand, and on at least one box
        // nobody had: credential emails sat in the jobs table unsent for days.
        //
        // Running both would be worse than either: two workers can reserve the
        // same job inside retry_after and send a parent their password twice.
        // If the systemd unit is ever retired, restore a drain here - do not
        // leave the queue with no consumer.
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
