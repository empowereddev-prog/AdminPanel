<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use Carbon\Carbon;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire old subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Subscription::where('end_date', '<', now())
            ->whereIn('status', ['Successful', 'cancelled'])
            ->update(['status' => 'expired']);

        $this->info('Subscriptions expired successfully');
    }
}
