<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\TestCase;

/**
 * Phase 2 moved every mobile endpoint onto App\Support\ApiResponse. A handful
 * of responses are deliberately left hand-rolled - `data => null` contracts,
 * a string-valued `status`, admin actions that happen to share a controller -
 * and each carries an @envelope-exempt comment saying why.
 *
 * This asserts there is no third category. Working controller-by-controller
 * missed Admin\VideoRequestController entirely, because only one of its eight
 * actions is on an api route and the file lives under Admin/. Deriving the list
 * from routes/api.php instead of from a reading of the tree is the point.
 */
class EnvelopeCoverageTest extends TestCase
{
    public function test_every_api_response_is_enveloped_or_explicitly_exempt(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = file_get_contents($root . '/routes/api.php');

        preg_match_all('/(\w+)::class/', $routes, $m);
        $classes = array_unique($m[1]);

        $files = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root . '/app/Http/Controllers')
        );
        foreach ($it as $file) {
            if ($file->getExtension() === 'php') {
                $files[basename($file->getPathname(), '.php')] = $file->getPathname();
            }
        }

        $unexplained = [];

        foreach ($classes as $class) {
            if (!isset($files[$class])) {
                continue;
            }

            $path = $files[$class];
            $lines = explode("\n", file_get_contents($path));

            // A file-level marker exempts the whole class (webhooks, admin CRUD
            // sharing a file with one mobile action).
            $classExempt = str_contains(implode("\n", array_slice($lines, 0, 40)), '@envelope-exempt');

            foreach ($lines as $i => $line) {
                if (!str_contains($line, 'response()->json')) {
                    continue;
                }
                if (str_starts_with(ltrim($line), '//')) {
                    continue;
                }
                if ($classExempt) {
                    continue;
                }

                $before = implode("\n", array_slice($lines, max(0, $i - 10), min(10, $i)));

                if (!str_contains($before, '@envelope-exempt')) {
                    $rel = str_replace($root . '/', '', $path);
                    $unexplained[] = $rel . ':' . ($i + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $unexplained,
            "Hand-rolled response()->json on an api-routed controller with no "
            . "@envelope-exempt rationale:\n  - " . implode("\n  - ", $unexplained)
        );
    }
}
