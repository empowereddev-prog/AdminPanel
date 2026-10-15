<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;

class SchoolChildDetailController extends Controller
{
    public function show($id, $childId)
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);
        abort_unless($permission && $permission->is_modify === 'yes', 403);
        $school = School::findOrFail($id);
        $child = User::with('parent')->where('user_role_id', 4)
            ->where(function ($query) use ($school) {
                $query->where(function ($independent) use ($school) {
                    $independent->where('school_id', $school->id)->whereNull('parent_id');
                })->orWhereHas('parent', function ($parent) use ($school) {
                    $parent->where('school_id', $school->id)->where('user_role_id', 3);
                });
            })->findOrFail($childId);

        $child->categoryProgress = app(\App\Services\School\SchoolUserVideoProgress::class)->forUser($child);

        return view('admin.schoolManagement.child-detail', compact('school', 'child'));
    }
}
