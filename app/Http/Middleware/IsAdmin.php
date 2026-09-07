<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized');
        }

        $user = auth()->user();
        // dd($user);
        // Allow roles: 'admin' and 'subAdmin'
        if (!in_array($user->user_type, ['admin','subadmin'])) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
