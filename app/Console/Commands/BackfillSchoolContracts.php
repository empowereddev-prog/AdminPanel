<?php

namespace App\Console\Commands;

use App\Services\School\SchoolContractService;
use Illuminate\Console\Command;

/**
 * One school_subscriptions row per school that does not already have one.
 * Does not add up parent grant prices — those are not school revenue.
 */
class BackfillSchoolContracts extends Command
{
    protected $signature = 'school:backfill-contracts';

    protected $description = 'Write one school_subscriptions row for every school that has none. Idempotent.';

    public function handle(SchoolContractService $contracts): int
    {
        $result = $contracts->backfillMissing();

        $this->info(sprintf(
            'School contracts: created %d, already present %d.',
            $result['created'],
            $result['skipped']
        ));

        if ($result['created'] > 0) {
            $this->comment('Schools without a stored price were recorded at 0.00. Set Subscription price on Edit School if the contract should show a paid amount.');
        }

        return self::SUCCESS;
    }
}
