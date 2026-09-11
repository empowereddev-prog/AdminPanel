<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\TestCase;

/**
 * A file that calls ApiResponse:: without importing it lints clean - php -l
 * does not resolve class names - and only 500s at runtime. During the Phase 2
 * migration that happened twice, in QuizController and MeetTeamController, and
 * both times the single thing that caught it was a snapshot case that happened
 * to exercise the endpoint. Endpoints with no snapshot case would have shipped
 * broken.
 *
 * This checks every controller statically, so coverage of the gate is not what
 * stands between a missing import and production.
 */
class ApiResponseImportTest extends TestCase
{
    public function test_every_controller_using_the_envelope_imports_it(): void
    {
        $root = dirname(__DIR__, 3) . '/app/Http/Controllers';
        $missing = [];

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            if (!str_contains($source, 'ApiResponse::')) {
                continue;
            }

            // A fully-qualified call needs no import.
            if (str_contains($source, '\App\Support\ApiResponse::')) {
                continue;
            }

            if (!str_contains($source, 'use App\Support\ApiResponse;')) {
                $missing[] = str_replace($root . '/', '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These controllers call ApiResponse:: without importing it:\n  - " . implode("\n  - ", $missing)
        );
    }
}
