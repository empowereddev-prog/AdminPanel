<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventUserLoginPageAccess
{
    public function handle(Request $request, Closure $next)
    {
        // This middleware is not needed for the current requirements.
        return $next($request);
    }
}
