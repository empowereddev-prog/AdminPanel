<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticatedBack
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard'); // apna dashboard route
        }
        return $next($request);
    }
}
