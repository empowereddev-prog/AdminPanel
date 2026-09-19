<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsToVideoUpload
{
    protected function videoSavedResponse(Request $request, string $routeName, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'redirect' => route($routeName),
                'message' => $message,
            ]);
        }

        return redirect()->route($routeName)->with('success', $message);
    }
}
