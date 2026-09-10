<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * The single response envelope for the mobile API.
 *
 * Api\ResponseController could not express an error at all - every one of its
 * methods hardcoded response()->json($response) with no status argument - which
 * is why it had two call sites and every handler hand-rolled its own shape.
 *
 * The envelope is always {status, message, data}. Under v1 any legacy aliases a
 * caller passes are mirrored alongside it, so the shipped app keeps finding the
 * keys it reads today; v2 emits the clean shape only. See ApiVersion.
 */
class ApiResponse
{
    /**
     * @param  array<string,mixed>  $legacy  top-level keys kept for v1 clients only
     * @param  array<string,mixed>  $extra   top-level keys kept in every version
     *                                       (the auth token, which a v2 client
     *                                       still needs, is the reason this exists)
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $legacy = [],
        array $extra = []
    ): JsonResponse {
        return self::make(true, $message ?? 'Action performed successfully', $data, $status, null, $legacy, $extra);
    }

    /**
     * @param  mixed  $errors  per-field validation errors, if any
     */
    public static function error(
        ?string $message = null,
        int $status = 400,
        mixed $errors = null,
        mixed $data = null,
        array $legacy = []
    ): JsonResponse {
        return self::make(false, $message ?? 'Unable to perform this action', $data, $status, $errors, $legacy);
    }

    /**
     * Normalises the three pagination shapes the API currently emits into one,
     * keeping the legacy flat keys for v1 callers.
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        array $legacy = []
    ): JsonResponse {
        $meta = [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];

        if (ApiVersion::isV2()) {
            return self::make(true, $message ?? 'Data fetched successfully', [
                'items' => $paginator->items(),
                'meta'  => $meta,
            ], 200, null, $legacy);
        }

        // v1: the paginator's own keys, flattened under data, as the app reads them.
        return self::make(
            true,
            $message ?? 'Data fetched successfully',
            array_merge(['data' => $paginator->items()], $meta),
            200,
            null,
            array_merge(['meta' => $meta], $legacy)
        );
    }

    private static function make(
        bool $status,
        string $message,
        mixed $data,
        int $httpStatus,
        mixed $errors,
        array $legacy,
        array $extra = []
    ): JsonResponse {
        $payload = [
            'status'  => $status,
            'message' => $message,
            'data'    => $data ?? (object) [],
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        // Additive only: neither map may overwrite a canonical key.
        if ($extra !== []) {
            $payload += $extra;
        }

        if (!ApiVersion::isV2() && $legacy !== []) {
            $payload += $legacy;
        }

        return response()->json($payload, $httpStatus);
    }
}
