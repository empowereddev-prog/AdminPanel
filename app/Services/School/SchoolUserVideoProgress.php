<?php

namespace App\Services\School;

use App\Models\Category;
use App\Models\User;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;

class SchoolUserVideoProgress
{
    public function forUser(User $user): array
    {
        $videos = VideoContent::whereIn('user_type', ['child', 'both'])->get();
        $history = UserContentWatchHistory::where('child_id', $user->id)
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
        return $progress;
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