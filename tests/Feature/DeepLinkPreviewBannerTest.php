<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeepLinkPreviewBannerTest extends TestCase
{
    private function previewData(): array
    {
        return [
            'item' => [
                'title' => 'Sample article',
                'teaser' => 'Teaser',
                'accent' => '#1a5edb',
                'canonical_url' => 'https://admin.empoweredhealth.asia/d/article/1',
                'banner' => null,
                'type' => 'article',
                'category' => 'Health',
                'audience_label' => 'For parents & staff',
                'author' => 'Staff',
            ],
            'iosStore' => 'https://apps.apple.com/app/empowered-health/id6742772237',
            'iosStoreApp' => 'itms-apps://apps.apple.com/app/id6742772237',
            'androidStore' => 'https://play.google.com/store/apps/details?id=asia.empoweredhealth',
            'iosAppId' => '6742772237',
            'appIcon' => 'https://admin.empoweredhealth.asia/assets/images/new.png',
            'schemeUrl' => 'empowered://article/1',
            'androidIntent' => 'intent://article/1#Intent;scheme=empowered;package=asia.empoweredhealth;end',
        ];
    }

    public function test_preview_does_not_emit_safari_native_smart_app_banner(): void
    {
        $view = $this->view('deeplink.preview', $this->previewData());

        $view->assertDontSee('<meta name="apple-itunes-app"', false);
        $view->assertDontSee('rel="manifest"', false);
        $view->assertSee('id="smart-banner"', false);
    }

    public function test_web_manifest_does_not_prefer_native_play_install_banner(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk();
        $payload = $response->json();
        $this->assertFalse($payload['prefer_related_applications'] ?? true);
    }
}
