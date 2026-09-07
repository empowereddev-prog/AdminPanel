<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PruneAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete audit logs older than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $deleted = DB::table('audits')
            ->where('created_at', '<', $thirtyDaysAgo)
            ->delete();

        $this->info("Successfully deleted {$deleted} old audit logs.");
        return Command::SUCCESS;
    }
}
