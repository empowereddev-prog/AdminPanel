<?php

namespace App\Http\Controllers;

use App\Services\DeepLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeepLinkController extends Controller
{
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
                'relation' => [
                    'delegate_permission/common.handle_all_urls',
                    'delegate_permission/common.get_login_creds',
                ],
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
        $web = $this->webFallbackUrl();
        $type = DeepLinkService::normalizeType($type);
        $id = filter_var($id, FILTER_VALIDATE_INT);

        if ($type === '' || $id === false || $id < 1) {
            return redirect()->away($web);
        }

        $scheme = DeepLinkService::customSchemeUrl($type, $id);
        $package = config('deeplink.android_package');
        $androidIntent = 'intent://' . $type . '/' . $id
            . '#Intent;scheme=' . config('deeplink.scheme')
            . ';package=' . $package
            . ';S.browser_fallback_url=' . rawurlencode($web)
            . ';end';

        return response()
            ->view('deeplink.bounce', [
                'webFallbackUrl' => $web,
                'schemeUrl' => $scheme,
                'androidIntent' => $androidIntent,
            ])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * Target of the app-download QR code. Phones go straight to their store;
     * everything else (and ?show) gets a landing page with both store badges.
     * no-store so a proxy never hands one platform's redirect to another.
     */
    public function appDownload(Request $request)
    {
        $ios = (string) config('deeplink.ios_store_url');
        $android = (string) config('deeplink.android_store_url');

        if (! $request->has('show')) {
            $ua = (string) $request->userAgent();

            if (preg_match('/iPhone|iPad|iPod/i', $ua)) {
                return redirect()->away($ios)->header('Cache-Control', 'no-store');
            }

            if (preg_match('/Android/i', $ua)) {
                return redirect()->away($android)->header('Cache-Control', 'no-store');
            }
        }

        return response()
            ->view('deeplink.app-download', [
                'iosStoreUrl' => $ios,
                'androidStoreUrl' => $android,
                'webFallbackUrl' => $this->webFallbackUrl(),
                'appIcon' => (string) config('deeplink.app_icon'),
                'autoRedirect' => ! $request->has('show'),
            ])
            ->header('Cache-Control', 'no-store');
    }

    protected function webFallbackUrl(): string
    {
        $url = rtrim((string) config('deeplink.web_fallback_url'), '/');

        return $url !== '' ? $url : 'https://empoweredhealth.asia';
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
