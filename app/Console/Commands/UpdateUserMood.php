<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User; // Ensure this model is correctly imported
use Illuminate\Support\Facades\Mail;
class UpdateUserMood extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user-mood';

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
        $user_details = User::where('status','active')->whereNull('deleted_at')->get();

        foreach ($user_details as $user) {
           $user->update([
            'is_mood_updated' => 'no'
           ]);
                \Log::info('= user_details: ' . $user);
                $this->info("Sent expiry notification to: $user");
           
        }
    }
}
