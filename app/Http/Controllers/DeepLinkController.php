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

        return response()
            ->view('deeplink.preview', [
                'item' => $resolved,
                'iosStore' => config('deeplink.ios_store_url'),
                'androidStore' => config('deeplink.android_store_url'),
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
}
