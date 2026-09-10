<?php

namespace App\Console\Commands;

use App\Services\DeepLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites stored canonical_url values onto the configured public base URL.
 *
 * canonical_url is persisted, not computed: the add_canonical_url migration
 * backfilled every row using env('DEEPLINK_PUBLIC_BASE_URL', env('APP_URL')),
 * and both DeepLinkService::resolve() and the model accessors return the stored
 * value whenever it is non-empty. So when that base was wrong - a bare server
 * IP over http, which no Universal Link or App Link can ever verify - fixing
 * the environment does not fix the existing rows. This does.
 */
class RebaseDeepLinkCanonicalUrls extends Command
{
    protected $signature = 'deeplink:rebase-canonical-urls
                            {--dry-run : Report what would change without writing}
                            {--force : Proceed even if the configured base URL fails its sanity check}';

    protected $description = 'Rewrite stored deep link canonical_url values onto the configured public base URL';

    /** @var array<string,string> table => deep link type */
    private const TARGETS = [
        'knowledge_sessions' => DeepLinkService::TYPE_ARTICLE,
        'video_contents' => DeepLinkService::TYPE_PODCAST,
    ];

    public function handle(): int
    {
        $base = rtrim((string) config('deeplink.public_base_url'), '/');
        $dryRun = (bool) $this->option('dry-run');

        if ($base === '') {
            $this->error('deeplink.public_base_url is empty. Set DEEPLINK_PUBLIC_BASE_URL before running this.');

            return self::FAILURE;
        }

        if ($problem = $this->sanityProblem($base)) {
            $this->error("Refusing to rebase onto {$base}: {$problem}");

            if (!$this->option('force')) {
                $this->line('Fix DEEPLINK_PUBLIC_BASE_URL (and clear the config cache), or pass --force if you really mean it.');

                return self::FAILURE;
            }

            $this->warn('--force given; continuing anyway.');
        }

        $this->info(($dryRun ? '[dry run] ' : '') . "Rebasing canonical URLs onto {$base}");

        $totalChanged = 0;

        foreach (self::TARGETS as $table => $type) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'canonical_url')) {
                $this->warn("  {$table}: no canonical_url column, skipped.");

                continue;
            }

            $changed = $this->rebaseTable($table, $type, $base, $dryRun);
            $totalChanged += $changed;

            $this->line("  {$table}: {$changed} row(s) " . ($dryRun ? 'would be updated' : 'updated'));
        }

        $this->info(($dryRun ? '[dry run] ' : '') . "Done. {$totalChanged} row(s) " . ($dryRun ? 'would change' : 'changed') . '.');

        if ($dryRun && $totalChanged > 0) {
            $this->line('Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    private function rebaseTable(string $table, string $type, string $base, bool $dryRun): int
    {
        $changed = 0;
        $samples = 0;

        DB::table($table)
            ->select('id', 'canonical_url')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $type, $base, $dryRun, &$changed, &$samples) {
                foreach ($rows as $row) {
                    $expected = $base . '/d/' . $type . '/' . $row->id;

                    if (($row->canonical_url ?? null) === $expected) {
                        continue;
                    }

                    if ($samples < 3) {
                        $this->line(sprintf(
                            '    #%d  %s  ->  %s',
                            $row->id,
                            $row->canonical_url ?: '(null)',
                            $expected
                        ));
                        $samples++;
                    }

                    if (!$dryRun) {
                        // Written through the query builder on purpose: this
                        // must not touch updated_at or fire model events.
                        DB::table($table)->where('id', $row->id)->update(['canonical_url' => $expected]);
                    }

                    $changed++;
                }
            });

        return $changed;
    }

    /**
     * The whole point of this command is that a bad base URL gets baked into
     * every row, so refuse the bases that caused the original problem.
     */
    private function sanityProblem(string $base): ?string
    {
        $host = parse_url($base, PHP_URL_HOST);
        $scheme = parse_url($base, PHP_URL_SCHEME);

        if (!$host || !$scheme) {
            return 'it is not a valid absolute URL.';
        }

        // Most specific reason first: a bare IP is both un-verifiable and
        // usually also http, and the IP is the more useful thing to report.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return 'it is a bare IP address, which cannot be verified via .well-known and will never open the app.';
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return 'it points at localhost.';
        }

        if ($scheme !== 'https') {
            return 'Universal Links and App Links are only honoured over https.';
        }

        return null;
    }
}
