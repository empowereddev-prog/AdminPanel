<?php

namespace Tests\Feature\School;

use Tests\TestCase;

/**
 * Pins the cause of a real "CSRF token mismatch" on the school screens.
 *
 * `route()` and `url()` build absolute URLs from APP_URL. When APP_URL says
 * 127.0.0.1 and the admin is browsing localhost (or vice versa), every AJAX
 * call leaves the origin the page was served from. The session cookie is scoped
 * to that origin, so it is not attached, Laravel starts a fresh session, and the
 * POST fails token verification — surfacing as "CSRF token mismatch" on an
 * action that looks perfectly ordinary.
 *
 * Host-relative URLs always follow the address bar, so they cannot drift.
 *
 * Note: CSRF itself cannot be exercised in a feature test — VerifyCsrfToken
 * returns early under `runningUnitTests()` — so this guards the cause rather
 * than the symptom.
 */
class AdminAjaxUrlTest extends TestCase
{
    private const VIEWS = [
        'admin/schoolManagement/view.blade.php',
        'admin/schoolManagement/index.blade.php',
    ];

    public function test_admin_ajax_urls_are_host_relative(): void
    {
        $checked = 0;

        foreach (self::VIEWS as $view) {
            $blade = file_get_contents(resource_path('views/' . $view));

            preg_match_all("/(?:url|ajax):\s*'([^']+)'/", $blade, $matches);

            foreach ($matches[1] as $url) {
                $checked++;
                $this->assertStringStartsNotWith(
                    'http',
                    $url,
                    "{$view} has an absolute AJAX URL: {$url}\n"
                    . 'Absolute URLs come from APP_URL and break the session cookie when the '
                    . 'browser is on a different host, which surfaces as "CSRF token mismatch".'
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'No AJAX URLs were found to check — has the markup changed?');
    }
}
