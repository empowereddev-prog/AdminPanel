<?php

namespace App\Services\School;

use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\UserAttemptQuiz;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;

class SchoolUserVideoProgress
{
    /**
     * Video progress per active category, measured against the videos this user
     * can actually watch (parents: parent + both, everyone else: child + both).
     * Mirrors the app's `get-category` calculation so the admin panel and the
     * app show the same figure: a completed video counts in full, a partial one
     * is capped at the video's duration.
     *
     * @return array<int,array{category_id:int,category_name:string,percentage:float|int}>
     */
    public function forUser(User $user): array
    {
        $isParent = (int) $user->user_role_id === 3 || $user->user_type === 'parent';
        $videos = VideoContent::whereIn('user_type', $isParent ? ['parent', 'both'] : ['child', 'both'])->get();
        $history = UserContentWatchHistory::where('child_id', $user->id)
            ->whereIn('video_content_id', $videos->pluck('id'))->get()->groupBy('video_content_id');
        $byCategory = $videos->groupBy('category_id');
        $progress = [];
        $categories = Category::where('status', 'active')->get()->sortBy(fn ($category) => (int) $category->priority);
        foreach ($categories as $category) {
            $total = 0;
            $watched = 0;
            foreach ($byCategory->get($category->id, collect()) as $video) {
                $duration = $this->seconds($video->video_duration);
                $total += $duration;
                $entries = $history->get($video->id, collect());
                if ($entries->contains(fn ($entry) => $entry->is_completed === 'yes')) {
                    $watched += $duration;
                    continue;
                }
                $watched += min($duration, $entries->max(fn ($entry) => $this->seconds($entry->last_watched_duration)) ?? 0);
            }
            $progress[$category->id] = [
                'category_id' => $category->id,
                'category_name' => $category->category_name,
                'percentage' => $total > 0 ? min(100, round($watched / $total * 100, 2)) : 0,
            ];
        }
        return $progress;
    }

    /**
     * Quiz progress per quiz category: marks obtained over marks available.
     * Used for student accounts (the old per-parent Edit User page showed the
     * same figures for linked children).
     *
     * @return array<int,array{category_name:string,percentage:float|int,obtained_marks:mixed,total_marks:mixed}>
     */
    public function quizForUser(User $user): array
    {
        $progress = [];
        foreach (QuizCategory::where('status', 'active')->get()->sortBy(fn ($c) => (int) $c->priority) as $category) {
            $quizIds = Quiz::where('quiz_category_id', $category->id)->pluck('id');
            $total = QuizQuestion::whereIn('quiz_id', $quizIds)->where('status', 'active')->sum('marks');
            $obtained = UserAttemptQuiz::where('user_id', $user->id)->whereIn('quiz_id', $quizIds)->sum('marks_obtained');
            $progress[$category->id] = [
                'category_name' => $category->category_name,
                'percentage' => $total > 0 ? min(100, round($obtained / $total * 100, 2)) : 0,
                'obtained_marks' => $obtained,
                'total_marks' => $total,
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