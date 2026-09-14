<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeepLinkBounceTest extends TestCase
{
    public function test_article_fallback_bounces_to_marketing_site_without_preview(): void
    {
        $response = $this->get('/d/article/1');

        $response->assertOk();
        $response->assertSee('empoweredhealth.asia', false);
        $response->assertSee('empowered://article/1', false);
        $response->assertSee('intent://article/1', false);
        $response->assertDontSee('<meta name="apple-itunes-app"', false);
        $response->assertDontSee('App Store', false);
        $response->assertDontSee('Google Play', false);
        $response->assertDontSee('smart-banner', false);
        $response->assertDontSee('Preview only', false);
    }

    public function test_unpublished_or_missing_ids_still_bounce_not_404_html(): void
    {
        $response = $this->get('/d/podcast/999999999');

        $response->assertOk();
        $response->assertSee('empoweredhealth.asia', false);
        $response->assertSee('empowered://podcast/999999999', false);
        $response->assertDontSee('Content unavailable', false);
    }

    public function test_aasa_paths_are_unchanged(): void
    {
        $response = $this->get('/.well-known/apple-app-site-association');

        $response->assertOk();
        $payload = $response->json();
        $components = $payload['applinks']['details'][0]['components'] ?? [];
        $paths = array_column($components, '/');

        $this->assertContains('/d/article/*', $paths);
        $this->assertContains('/d/podcast/*', $paths);
    }
}
