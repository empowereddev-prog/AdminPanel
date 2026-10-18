<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppDownloadPageTest extends TestCase
{
    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';
    private const ANDROID = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36';
    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36';

    public function test_iphone_is_sent_to_the_app_store(): void
    {
        $response = $this->withHeader('User-Agent', self::IPHONE)->get('/app');

        $response->assertRedirect(config('deeplink.ios_store_url'));
        $this->assertStringContainsString('id6742772237', $response->headers->get('Location'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_android_is_sent_to_google_play(): void
    {
        $response = $this->withHeader('User-Agent', self::ANDROID)->get('/app');

        $response->assertRedirect(config('deeplink.android_store_url'));
        $this->assertStringContainsString('asia.empoweredhealth', $response->headers->get('Location'));
    }

    public function test_desktop_gets_the_landing_page_with_both_stores(): void
    {
        $response = $this->withHeader('User-Agent', self::DESKTOP)->get('/app');

        $response->assertOk();
        $response->assertSee(config('deeplink.ios_store_url'), false);
        $response->assertSee(config('deeplink.android_store_url'), false);
        $response->assertSee('empoweredhealth.asia', false);
        $response->assertSee('App Store', false);
        $response->assertSee('Google Play', false);
    }

    public function test_show_param_skips_the_redirect_on_a_phone(): void
    {
        $response = $this->withHeader('User-Agent', self::IPHONE)->get('/app?show=1');

        $response->assertOk();
        $response->assertSee('EmpowerED Health', false);
        $response->assertDontSee('window.location.replace', false);
    }
}
