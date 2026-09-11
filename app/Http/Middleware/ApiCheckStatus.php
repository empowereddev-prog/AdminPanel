<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use App\Support\ApiVersion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCheckStatus
{
    /**
     * Rejects tokens whose account is no longer allowed to use the API.
     *
     * This previously read `auth()->check() && ...`, so an unauthenticated
     * request passed straight through - it was a no-op gate that only worked
     * because auth:api happens to run first in routes/api.php. It also checked
     * only users.status, while the school-inactive rule lived inline in two
     * controllers and applied nowhere else.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return ApiResponse::error('Unauthenticated.', 401);
        }

        if ($user->status !== 'active') {
            return ApiResponse::error(
                'User account is inactive',
                // 403 is correct for "authenticated but disabled"; v1 keeps the
                // 401 the shipped app already handles.
                ApiVersion::isV2($request) ? 403 : 401,
                null,
                null,
                ['message' => 'User account is inactive']
            );
        }

        if ($this->schoolIsInactive($user)) {
            return ApiResponse::error(
                'Your school account has been deactivated. Please contact your administration.',
                ApiVersion::isV2($request) ? 403 : 401
            );
        }

        return $next($request);
    }

    /**
     * A teacher whose school was deactivated kept a working token on every
     * endpoint except login and get-profile, where the check was duplicated.
     */
    private function schoolIsInactive($user): bool
    {
        if (empty($user->school_id) || is_array($user->school_id)) {
            return false;
        }

        // Matches the inline check in HomeApiController::login exactly.
        $status = \DB::table('schools')->where('id', $user->school_id)->value('status');

        return $status === 'inactive';
    }
}
