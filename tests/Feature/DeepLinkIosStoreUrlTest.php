<?php

namespace Tests\Feature;

use App\Http\Controllers\DeepLinkController;
use Tests\TestCase;

class DeepLinkIosStoreUrlTest extends TestCase
{
    private function storeUrl(): string
    {
        $controller = $this->app->make(DeepLinkController::class);
        $method = new \ReflectionMethod($controller, 'iosStoreUrl');

        return $method->invoke($controller);
    }

    public function test_store_url_includes_empowered_health_app_id(): void
    {
        config()->set('deeplink.ios_app_id', '6742772237');
        config()->set('deeplink.ios_store_url', 'https://apps.apple.com');

        $url = $this->storeUrl();

        $this->assertStringContainsString('id6742772237', $url);
        $this->assertStringStartsWith('https://apps.apple.com/', $url);
        $this->assertNotSame('https://apps.apple.com', $url);
    }

    public function test_generic_apps_apple_url_is_replaced(): void
    {
        config()->set('deeplink.ios_app_id', '6742772237');
        config()->set('deeplink.ios_store_url', 'https://apps.apple.com/');

        $this->assertSame(
            'https://apps.apple.com/app/empowered-health/id6742772237',
            $this->storeUrl()
        );
    }
}
