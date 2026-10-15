<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolUserVideoProgress;

class SchoolTeacherDetailController extends Controller
{
    public function show($id, $teacherId)
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);
        abort_unless($permission && $permission->is_modify === 'yes', 403);
        $school = School::findOrFail($id);
        $teacher = User::where('school_id', $school->id)->where('user_role_id', 5)->findOrFail($teacherId);
        $teacher->categoryProgress = app(SchoolUserVideoProgress::class)->forUser($teacher);
        return view('admin.schoolManagement.teacher-detail', compact('school', 'teacher'));
    }
}