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

        // Drains the queue. The school import dispatches SendStudentSignupMail
        // rather than mailing inline - sending N credential emails inside one
        // HTTP request times out a large import - and this deployment runs no
        // worker process. Without this the jobs would sit in the table unsent,
        // which is the same outcome as the missing template, reached a
        // different way. --stop-when-empty exits as soon as the queue drains;
        // --max-time keeps a run from colliding with the next minute's.
        //
        // If a supervisor-managed `queue:work` is introduced later, remove this.
        $schedule->command('queue:work --stop-when-empty --max-time=55 --tries=3')
            ->everyMinute()
            ->withoutOverlapping();
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
