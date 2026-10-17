<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SchoolChildDetailController extends Controller
{
    public function show($id, $childId)
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);
        abort_unless($permission && $permission->is_modify === 'yes', 403);
        $school = School::findOrFail($id);
        $child = $this->schoolChild($school, $childId);

        $progress = app(\App\Services\School\SchoolUserVideoProgress::class);
        $child->categoryProgress = $progress->forUser($child);
        $child->quizProgress = $progress->quizForUser($child);

        return view('admin.schoolManagement.child-detail', compact('school', 'child'));
    }

    public function destroy($id, $childId)
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);

        if (! $permission || $permission->is_modify !== 'yes') {
            return response()->json(['status' => false, 'message' => 'You do not have permission to do this.'], 403);
        }

        $school = School::findOrFail($id);
        $child = $this->schoolChild($school, $childId);

        $child->deleteChildAccount();

        // A soft delete frees the seat automatically, because the seat count
        // filters on deleted_at. Logged so that a school cycling children to
        // stay under its cap is visible rather than invisible.
        Log::info('School child seat released', [
            'school_id' => $school->id,
            'parent_id' => $child->parent_id,
            'child_id' => $child->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Child account deleted.']);
    }

    /**
     * A child belonging to this school: independent (school_id, no parent) or
     * linked through a parent that is in the school. 404s for anyone else.
     */
    private function schoolChild(School $school, $childId): User
    {
        return User::with('parent')->where('user_role_id', 4)
            ->where(function ($query) use ($school) {
                $query->where(function ($independent) use ($school) {
                    $independent->where('school_id', $school->id)->whereNull('parent_id');
                })->orWhereHas('parent', function ($parent) use ($school) {
                    $parent->where('school_id', $school->id)->where('user_role_id', 3);
                });
            })->findOrFail($childId);
    }
}
