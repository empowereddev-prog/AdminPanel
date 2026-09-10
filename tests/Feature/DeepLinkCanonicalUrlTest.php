<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeepLinkCanonicalUrlTest extends TestCase
{
    use DatabaseTransactions;

    private function seedSession(?string $canonicalUrl): int
    {
        return DB::table('knowledge_sessions')->insertGetId([
            'title' => 'Rebase fixture',
            'status' => 'active',
            'category_id' => 1, // NOT NULL with no default on this table
            'canonical_url' => $canonicalUrl,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** The config must not fall back to APP_URL - that is what baked an IP into every row. */
    public function test_config_does_not_fall_back_to_app_url(): void
    {
        config()->set('app.url', 'http://13.229.56.31');

        $contents = file_get_contents(config_path('deeplink.php'));

        $this->assertStringNotContainsString(
            "env('APP_URL'",
            $contents,
            'deeplink.public_base_url must not fall back to APP_URL.'
        );
    }

    public function test_rebase_rewrites_a_stale_ip_base(): void
    {
        $id = $this->seedSession('http://13.229.56.31/d/article/999');

        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(0);

        $this->assertSame(
            rtrim(config('deeplink.public_base_url'), '/') . '/d/article/' . $id,
            DB::table('knowledge_sessions')->where('id', $id)->value('canonical_url')
        );
    }

    public function test_rebase_fills_a_null_canonical_url(): void
    {
        $id = $this->seedSession(null);

        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(0);

        $this->assertSame(
            rtrim(config('deeplink.public_base_url'), '/') . '/d/article/' . $id,
            DB::table('knowledge_sessions')->where('id', $id)->value('canonical_url')
        );
    }

    /** A backfill must not look like a content edit. */
    public function test_rebase_does_not_touch_updated_at(): void
    {
        $id = $this->seedSession('http://13.229.56.31/d/article/999');
        DB::table('knowledge_sessions')->where('id', $id)->update(['updated_at' => '2020-01-01 00:00:00']);

        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(0);

        $this->assertSame(
            '2020-01-01 00:00:00',
            (string) DB::table('knowledge_sessions')->where('id', $id)->value('updated_at')
        );
    }

    public function test_rebase_is_idempotent(): void
    {
        $this->seedSession('http://13.229.56.31/d/article/999');

        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(0);
        $this->artisan('deeplink:rebase-canonical-urls')
            ->expectsOutputToContain('0 row(s) changed.')
            ->assertExitCode(0);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $id = $this->seedSession('http://13.229.56.31/d/article/999');

        $this->artisan('deeplink:rebase-canonical-urls', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(
            'http://13.229.56.31/d/article/999',
            DB::table('knowledge_sessions')->where('id', $id)->value('canonical_url')
        );
    }

    /** The guard is the point: never re-bake an unusable base. */
    public function test_rebase_refuses_a_bare_ip_base(): void
    {
        $id = $this->seedSession('https://admin.empoweredhealth.asia/d/article/1');
        config()->set('deeplink.public_base_url', 'http://13.229.56.31');

        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(1);

        $this->assertSame(
            'https://admin.empoweredhealth.asia/d/article/1',
            DB::table('knowledge_sessions')->where('id', $id)->value('canonical_url')
        );
    }

    public function test_rebase_refuses_http_and_localhost(): void
    {
        config()->set('deeplink.public_base_url', 'http://admin.empoweredhealth.asia');
        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(1);

        config()->set('deeplink.public_base_url', 'https://localhost');
        $this->artisan('deeplink:rebase-canonical-urls')->assertExitCode(1);
    }
}
