<?php

namespace App\Http\Controllers;

use App\Services\DeepLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeepLinkController extends Controller
{
    public function __construct(protected DeepLinkService $deepLinks)
    {
    }

    public function appleAppSiteAssociation(): Response
    {
        $appId = config('deeplink.apple_team_id') . '.' . config('deeplink.android_package');

        $payload = [
            'applinks' => [
                'details' => [
                    [
                        'appIDs' => [$appId],
                        'components' => [
                            ['/' => '/d/article/*', 'comment' => 'Articles'],
                            ['/' => '/d/podcast/*', 'comment' => 'Podcasts'],
                        ],
                    ],
                ],
            ],
        ];

        return $this->associationResponse($payload);
    }

    public function assetLinks(): Response
    {
        $fingerprints = config('deeplink.android_sha256_fingerprints', []);
        $payload = [
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('deeplink.android_package'),
                    'sha256_cert_fingerprints' => $fingerprints,
                ],
            ],
        ];

        return $this->associationResponse($payload);
    }

    public function webManifest(): Response
    {
        $icon = $this->appIconUrl();
        $payload = [
            'name' => 'Empowered Health',
            'short_name' => 'Empowered',
            'start_url' => '/',
            'display' => 'browser',
            'prefer_related_applications' => true,
            'related_applications' => [
                [
                    'platform' => 'play',
                    'id' => config('deeplink.android_package'),
                    'url' => config('deeplink.android_store_url'),
                ],
            ],
            'icons' => [
                [
                    'src' => $icon,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
            ],
        ];

        return response(
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            200,
            [
                'Content-Type' => 'application/manifest+json',
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }

    public function fallback(Request $request, string $type, $id)
    {
        $resolved = $this->deepLinks->resolve($type, $id, null, true);
        $http = $resolved['http_status'];

        if (in_array($resolved['status'], [DeepLinkService::STATUS_NOT_FOUND, DeepLinkService::STATUS_UNPUBLISHED], true)) {
            return response()
                ->view('deeplink.unavailable', [
                    'status' => $resolved['status'],
                ], 404)
                ->header('Cache-Control', 'no-store');
        }

        $resolved = $this->deepLinks->withPreviewDetails($resolved);
        $scheme = $resolved['custom_scheme_url'];
        $package = config('deeplink.android_package');
        $iosStore = $this->iosStoreUrl();
        $androidStore = (string) config('deeplink.android_store_url');
        $androidIntent = 'intent://' . $resolved['type'] . '/' . $resolved['id']
            . '#Intent;scheme=' . config('deeplink.scheme')
            . ';package=' . $package
            . ';S.browser_fallback_url=' . rawurlencode($androidStore)
            . ';end';

        return response()
            ->view('deeplink.preview', [
                'item' => $resolved,
                'iosStore' => $iosStore,
                'iosStoreApp' => $this->iosStoreAppUrl($iosStore),
                'androidStore' => $androidStore,
                'iosAppId' => $this->iosAppId(),
                'appIcon' => $this->appIconUrl(),
                'schemeUrl' => $scheme,
                'androidIntent' => $androidIntent,
            ], $http)
            ->header('Cache-Control', 'public, max-age=300');
    }

    protected function associationResponse(array $payload): Response
    {
        return response(
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            200,
            [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'public, max-age=3600',
            ]
        );
    }

    protected function iosAppId(): string
    {
        $id = trim((string) config('deeplink.ios_app_id'));
        if ($id !== '') {
            return $id;
        }
        if (preg_match('/(?:id|\/)(\d{9,12})/', (string) config('deeplink.ios_store_url'), $matches)) {
            return $matches[1];
        }

        return '';
    }

    protected function iosStoreUrl(): string
    {
        $id = $this->iosAppId();
        $configured = trim((string) config('deeplink.ios_store_url'));

        if ($id === '') {
            return $configured !== '' ? $configured : 'https://apps.apple.com';
        }

        if ($configured !== '' && preg_match('#^https://apps\.apple\.com/.+id' . preg_quote($id, '#') . '#i', $configured)) {
            return $configured;
        }

        return 'https://apps.apple.com/app/empowered-health/id' . $id;
    }

    protected function iosStoreAppUrl(string $httpsUrl): string
    {
        $id = $this->iosAppId();
        if ($id !== '') {
            return 'itms-apps://apps.apple.com/app/id' . $id;
        }

        return preg_replace('#^https?://#', 'itms-apps://', $httpsUrl) ?: $httpsUrl;
    }

    protected function appIconUrl(): string
    {
        $path = (string) config('deeplink.app_icon', '/assets/images/new.png');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
