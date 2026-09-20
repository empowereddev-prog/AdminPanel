<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the School Onboarding extras that are hidden from the admin panel.
 *
 * The matching views hide the controls, but hiding a button does not close the
 * route behind it - a bookmark or browser history would still reach it. This
 * closes that gap for the guarded routes.
 *
 * See config/scope.php for what "extras" covers and how to switch it back on.
 */
class EnsureSchoolExtrasEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('scope.school_extras')) {
            return $next($request);
        }

        // The roster actions are called over AJAX and their callers surface
        // `message` on failure, so answer in the shape those handlers expect
        // rather than redirecting a fetch into an HTML page.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => 'Not available.'], 404);
        }

        return redirect()->route('school.index');
    }
}
