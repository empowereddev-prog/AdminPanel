<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves which response contract a request wants.
 *
 * v1 is the shipped mobile app's contract: it must keep receiving the exact
 * keys and status codes it already receives. v2 is the corrected contract
 * (real 401/403/404/422, a per-field errors object) and is opted into with
 * the `Accept-Version: 2` request header.
 */
class ApiVersion
{
    public const HEADER = 'Accept-Version';

    public const V1 = 1;
    public const V2 = 2;

    public static function for(?Request $request = null): int
    {
        $request ??= request();

        if (!$request) {
            return self::V1;
        }

        return ((int) $request->header(self::HEADER)) >= self::V2
            ? self::V2
            : self::V1;
    }

    public static function isV2(?Request $request = null): bool
    {
        return self::for($request) === self::V2;
    }
}
