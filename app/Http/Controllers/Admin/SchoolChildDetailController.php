<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;

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

        $videos = VideoContent::whereIn('user_type', ['child', 'both'])->get();
        $history = UserContentWatchHistory::where('child_id', $child->id)
            ->whereIn('video_content_id', $videos->pluck('id'))->get()->groupBy('video_content_id');
        $byCategory = $videos->groupBy('category_id');
        $progress = [];
        foreach (Category::all() as $category) {
            $total = 0;
            $watched = 0;
            foreach ($byCategory->get($category->id, collect()) as $video) {
                $duration = $this->seconds($video->video_duration);
                $total += $duration;
                $watched += min($duration, $history->get($video->id, collect())
                    ->max(fn ($entry) => $this->seconds($entry->last_watched_duration)) ?? 0);
            }
            $progress[$category->id] = [
                'category_name' => $category->category_name,
                'percentage' => $total > 0 ? min(100, round($watched / $total * 100, 2)) : 0,
            ];
        }
        $child->categoryProgress = $progress;
        return view('admin.schoolManagement.child-detail', compact('school', 'child'));
    }

    private function seconds(?string $duration): int
    {
        $parts = array_map('intval', explode(':', $duration ?? ''));
        return match (count($parts)) {
            2 => max(0, $parts[0] * 60 + $parts[1]),
            3 => max(0, $parts[0] * 3600 + $parts[1] * 60 + $parts[2]),
            default => 0,
        };
    }
}