<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SchoolAccountModeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['school_code' => 'required|string|max:50']);
        $school = School::where('school_code', $request->school_code)->where('status', 'active')->first();
        if (!$school) {
            return ApiResponse::error('Invalid or inactive school code.', 404);
        }
        return ApiResponse::success([
            'account_mode' => $school->account_mode,
            'parent_signup_allowed' => $school->allowsParentCreation()
                && ($school->self_signup_enabled ?? 'yes') === 'yes',
            'child_creation_mode' => $school->account_mode === School::MODE_CHILD ? 'school_import' : 'parent_linked',
        ], 'School account mode retrieved.');
    }
}
