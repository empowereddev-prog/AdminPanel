<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use App\Support\ApiVersion;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    private function asVersion(?string $version): void
    {
        $request = Request::create('/api/example', 'POST');

        if ($version !== null) {
            $request->headers->set(ApiVersion::HEADER, $version);
        }

        $this->app->instance('request', $request);
    }

    public function test_defaults_to_v1_without_the_header(): void
    {
        $this->asVersion(null);

        $this->assertSame(ApiVersion::V1, ApiVersion::for());
        $this->assertFalse(ApiVersion::isV2());
    }

    public function test_accept_version_header_selects_v2(): void
    {
        $this->asVersion('2');

        $this->assertTrue(ApiVersion::isV2());
    }

    public function test_success_uses_the_canonical_envelope(): void
    {
        $this->asVersion(null);

        $payload = json_decode(ApiResponse::success(['a' => 1], 'Fetched')->getContent(), true);

        $this->assertSame(true, $payload['status']);
        $this->assertSame('Fetched', $payload['message']);
        $this->assertSame(['a' => 1], $payload['data']);
    }

    /** A null data payload must serialise as {}rather than null, as today. */
    public function test_null_data_serialises_as_an_object(): void
    {
        $this->asVersion(null);

        $json = ApiResponse::error('Nope', 400)->getContent();

        $this->assertStringContainsString('"data":{}', $json);
    }

    public function test_error_carries_the_status_code_and_errors(): void
    {
        $this->asVersion(null);

        $response = ApiResponse::error('Invalid', 422, ['name' => ['required']]);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['status']);
        $this->assertSame(['name' => ['required']], $payload['errors']);
    }

    /** v1 keeps legacy aliases so the shipped app still finds its keys. */
    public function test_legacy_aliases_are_mirrored_on_v1_only(): void
    {
        $this->asVersion(null);
        $v1 = json_decode(ApiResponse::success(['id' => 7], 'ok', 200, ['user' => ['id' => 7]])->getContent(), true);
        $this->assertArrayHasKey('user', $v1);
        $this->assertSame(['id' => 7], $v1['data']);

        $this->asVersion('2');
        $v2 = json_decode(ApiResponse::success(['id' => 7], 'ok', 200, ['user' => ['id' => 7]])->getContent(), true);
        $this->assertArrayNotHasKey('user', $v2);
    }

    /** A legacy alias must never overwrite a canonical key. */
    public function test_legacy_aliases_cannot_clobber_canonical_keys(): void
    {
        $this->asVersion(null);

        $payload = json_decode(
            ApiResponse::success(['real' => true], 'real message', 200, ['data' => 'spoofed', 'message' => 'spoofed'])->getContent(),
            true
        );

        $this->assertSame(['real' => true], $payload['data']);
        $this->assertSame('real message', $payload['message']);
    }

    public function test_paginated_keeps_flat_legacy_keys_on_v1(): void
    {
        $this->asVersion(null);

        $paginator = new LengthAwarePaginator([['id' => 1], ['id' => 2]], 12, 2, 1);
        $payload = json_decode(ApiResponse::paginated($paginator, 'Listed')->getContent(), true);

        $this->assertSame(12, $payload['data']['total']);
        $this->assertSame(2, $payload['data']['per_page']);
        $this->assertSame(1, $payload['data']['current_page']);
        $this->assertSame(6, $payload['data']['last_page']);
        $this->assertCount(2, $payload['data']['data']);
    }

    public function test_paginated_uses_items_and_meta_on_v2(): void
    {
        $this->asVersion('2');

        $paginator = new LengthAwarePaginator([['id' => 1]], 3, 1, 1);
        $payload = json_decode(ApiResponse::paginated($paginator, 'Listed')->getContent(), true);

        $this->assertCount(1, $payload['data']['items']);
        $this->assertSame(3, $payload['data']['meta']['total']);
        $this->assertArrayNotHasKey('meta', $payload);
    }
}
