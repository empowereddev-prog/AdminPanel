<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds school_parent_invites from the parents that already exist.
 *
 * This has to run, and its verification block has to come back clean, BEFORE
 * enforce_parent_roster is switched on for a school. Enabling the flag on a
 * school with no invite rows locks every one of its existing parents out.
 *
 * Idempotent: rerunning changes nothing.
 */
class BackfillSchoolRoster extends Command
{
    protected $signature = 'school:backfill-roster
                            {--school= : Restrict to a single school id}
                            {--dry-run : Report what would happen, write nothing}';

    protected $description = 'Write a claimed school_parent_invites row for every existing school parent. Idempotent.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run - no rows will be written.');
        }

        $schools = School::query()
            ->when($this->option('school'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id')
            ->get();

        if ($schools->isEmpty()) {
            $this->warn('No schools matched.');

            return self::SUCCESS;
        }

        $totals = ['parents' => 0, 'created' => 0, 'existing' => 0, 'revoked' => 0];
        $conflicts = [];

        foreach ($schools as $school) {
            $result = $this->backfillSchool($school, $dryRun, $conflicts);

            foreach ($totals as $key => $_) {
                $totals[$key] += $result[$key];
            }

            $this->line(sprintf(
                'School #%d "%s": %d parents | created %d, already-present %d, skipped-revoked %d, conflicts %d',
                $school->id,
                $school->name,
                $result['parents'],
                $result['created'],
                $result['existing'],
                $result['revoked'],
                $result['conflicts']
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            'Backfill complete. schools=%d parents=%d created=%d existing=%d skipped_revoked=%d conflicts=%d',
            $schools->count(),
            $totals['parents'],
            $totals['created'],
            $totals['existing'],
            $totals['revoked'],
            count($conflicts)
        ));

        if ($conflicts !== []) {
            $this->newLine();
            $this->error('Duplicate emails within a school - resolve these before enabling enforce_parent_roster:');
            foreach ($conflicts as $conflict) {
                $this->line('  - ' . $conflict);
            }
        }

        return $this->verify($schools, $dryRun, $conflicts !== []);
    }

    /**
     * @param  array<int,string>  $conflicts  collected by reference across schools
     * @return array{parents:int,created:int,existing:int,revoked:int,conflicts:int}
     */
    private function backfillSchool(School $school, bool $dryRun, array &$conflicts): array
    {
        $counts = ['parents' => 0, 'created' => 0, 'existing' => 0, 'revoked' => 0, 'conflicts' => 0];

        // Seen within this school, so two parent rows sharing one lowercased
        // address are reported rather than silently collapsing into one invite.
        $seen = [];

        User::query()
            ->where('school_id', $school->id)
            ->where('user_role_id', 3)          // parents only - teachers are role 5
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(200, function ($parents) use ($school, $dryRun, &$counts, &$seen, &$conflicts) {
                foreach ($parents as $parent) {
                    $email = SchoolParentInvite::normaliseEmail($parent->email);

                    if ($email === null) {
                        continue;
                    }

                    $counts['parents']++;

                    if (isset($seen[$email])) {
                        $counts['conflicts']++;
                        $conflicts[] = sprintf(
                            'school #%d %s - users #%d and #%d',
                            $school->id,
                            $email,
                            $seen[$email],
                            $parent->id
                        );

                        continue;
                    }

                    $seen[$email] = $parent->id;

                    $existing = SchoolParentInvite::where('school_id', $school->id)
                        ->where('email', $email)
                        ->first();

                    // An admin who revoked a parent after the first run must not
                    // have that undone by a rerun.
                    if ($existing && $existing->status === 'revoked') {
                        $counts['revoked']++;

                        continue;
                    }

                    if ($existing && $existing->claimed_user_id === $parent->id) {
                        $counts['existing']++;

                        continue;
                    }

                    $counts['created']++;

                    if ($dryRun) {
                        continue;
                    }

                    SchoolParentInvite::updateOrCreate(
                        ['school_id' => $school->id, 'email' => $email],
                        [
                            'name' => $parent->name,
                            'country_code' => $parent->country_code,
                            'phone_no' => $parent->phone_no,
                            'status' => 'claimed',
                            'claimed_user_id' => $parent->id,
                            'invited_at' => $parent->created_at,
                            'claimed_at' => $parent->created_at,
                        ]
                    );
                }
            });

        return $counts;
    }

    /**
     * The gate. Both numbers must be zero before any school is switched on,
     * and a non-zero exit lets a deploy script stop on it.
     */
    private function verify($schools, bool $dryRun, bool $hadConflicts): int
    {
        if ($dryRun) {
            $this->newLine();
            $this->warn('Verification skipped on a dry run.');

            return $hadConflicts ? self::FAILURE : self::SUCCESS;
        }

        $schoolIds = $schools->pluck('id');

        $unrostered = User::query()
            ->whereIn('school_id', $schoolIds)
            ->where('user_role_id', 3)
            ->whereNotNull('email')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('school_parent_invites')
                    ->whereColumn('school_parent_invites.school_id', 'users.school_id')
                    ->whereColumn('school_parent_invites.claimed_user_id', 'users.id');
            })
            ->count();

        $mismatched = DB::table('school_parent_invites as i')
            ->join('users as u', 'u.id', '=', 'i.claimed_user_id')
            ->whereIn('i.school_id', $schoolIds)
            ->whereRaw('u.school_id <> i.school_id OR u.school_id IS NULL')
            ->count();

        $this->newLine();
        $this->line('Verification:');
        $this->line("  parents with school_id and no claimed invite: {$unrostered}   (must be 0)");
        $this->line("  invites claimed to a user in a different school: {$mismatched}   (must be 0)");

        if ($unrostered > 0 || $mismatched > 0 || $hadConflicts) {
            $this->newLine();
            $this->error('Verification failed. Do NOT enable enforce_parent_roster until this is clean.');

            return self::FAILURE;
        }

        $this->info('Verification clean.');

        return self::SUCCESS;
    }
}
