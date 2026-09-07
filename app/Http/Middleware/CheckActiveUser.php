<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $id = Auth::user()->id;
        $user = User::where(["id" => $id, "status" => 'active'])->first();
        if (!$user) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account is either deactivated or no longer exists. Please contact the admin.');
        }
        return $next($request);
    }
}
