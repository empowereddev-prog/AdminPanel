<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // API clients that omit Accept: application/json were redirected to the
        // web login page. Returning null lets the AuthenticationException reach
        // the handler, which renders a 401 JSON body.
        if ($request->is('api/*') || $request->is('api')) {
            return null;
        }

        return $request->expectsJson() ? null : route('login');
    }
}
