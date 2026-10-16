<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SchoolSafetyFeatureController extends Controller
{
    public function __invoke(Request $request)
    {
        // Linked children inherit the school from their parent. Independent
        // students, teachers and school parents carry school_id themselves.
        $school = School::forUser($request->user());
        $enabled = $school?->safetyFeatureEnabled() ?? true;
        return ApiResponse::success([
            'school_id' => $school?->id,
            'needs_safety_feature' => $enabled ? 'yes' : 'no',
            'safety_feature_enabled' => $enabled,
            'show_get_help' => $enabled,
        ], 'School safety feature setting retrieved.');
    }
}
