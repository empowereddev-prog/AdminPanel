<?php

namespace App\Console\Commands;
use App\Notifications\AdminBroadcast;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-scheduled-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    { 
        $notifications = \App\Models\AdminNotification::where('is_sent', false)->get();

        foreach ($notifications as $notification) {
            $user = $notification->user;
            if ($user) {
                $user->notify(new \App\Notifications\AdminBroadcast($notification->title, $notification->message));
                $notification->update(['is_sent' => true]);
            }
        }
    
        $this->info('Notifications processed.');
    }
}
