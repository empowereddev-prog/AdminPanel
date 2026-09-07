<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;
use App\Models\User;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizQuestion;
use App\Models\School;
use App\Models\UserAttemptQuiz;
use App\Models\Mood;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SchoolUserDetailController extends Controller
{
    public function index(Request $request, $user_id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, 2);

        if (!empty($pre) && $pre->is_modify === 'yes') {
            $data = User::where('id', $user_id)->first();

            if (!$data) {
                return abort(404, 'User not found');
            }

            $children = User::where('parent_id', $user_id)->get();
            $categories = Category::all();

            foreach ($children as $child) {
                $child->age = now()->year - date('Y', strtotime($child->dob));
                $categoryProgress = [];

                foreach ($categories as $category) {
                    $categoryId = $category->id;
                    $categoryName = $category->category_name;

                    $videos = VideoContent::where('category_id', $categoryId)
                        ->whereIn('user_type', ['child', 'both'])
                        ->get();

                    $videoIds = $videos->pluck('id')->toArray();

                    $totalVideoSeconds = $videos->sum(function ($video) {
                        return $this->convertTimeToSeconds($video->video_duration);
                    });

                    $watchedDurations = UserContentWatchHistory::where('child_id', $child->id)
                        ->whereIn('video_content_id', $videoIds)
                        ->pluck('last_watched_duration')
                        ->toArray();

                    $watchedSeconds = array_sum(array_map([$this, 'convertTimeToSeconds'], $watchedDurations));

                    // $percentage = $totalVideoSeconds > 0
                    //     ? round(($watchedSeconds / $totalVideoSeconds) * 100, 2)
                    //     : 0;
                    $percentage = $totalVideoSeconds > 0
                        ? min(100, round(($watchedSeconds / $totalVideoSeconds) * 100, 2))
                        : 0;

                    $categoryProgress[$categoryId] = [
                        'category_name' => $categoryName,
                        'total_video_seconds' => $totalVideoSeconds,
                        'watched_seconds' => $watchedSeconds,
                        'percentage' => $percentage,
                        'total_video_duration' => gmdate("H:i:s", $totalVideoSeconds),
                    ];
                }
                // ---------- Quiz Progress ----------
                $quizCategories = QuizCategory::where('status', 'active')
                    ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
                    ->get();

                $quizProgress = [];
                foreach ($quizCategories as $quizCategory) {
                    $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');

                    $totalMarks = QuizQuestion::whereIn('quiz_id', $quizIds)
                        ->where('status', 'active')
                        ->sum('marks');

                    $obtainedMarks = UserAttemptQuiz::where('user_id', $child->id)
                        ->whereIn('quiz_id', $quizIds)
                        ->sum('marks_obtained');

                    $quizPercentage = $totalMarks > 0
                        ? round(($obtainedMarks / $totalMarks) * 100, 2)
                        : 0;

                    $quizProgress[$quizCategory->id] = [
                        'category_name' => $quizCategory->category_name,
                        'percentage' => $quizPercentage,
                        'obtained_marks' => $obtainedMarks,
                        'total_marks' => $totalMarks,
                    ];
                }
                $child->categoryProgress = $categoryProgress;
                $child->quizProgress = $quizProgress;
            }
            // ================= Parent Video Progress =================
            $parentCategoryProgress = [];

            foreach ($categories as $category) {
                $categoryId = $category->id;
                $categoryName = $category->category_name;

                // Parent videos
                $videos = VideoContent::where('category_id', $categoryId)
                    ->whereIn('user_type', ['parent', 'both'])
                    ->get();

                $videoIds = $videos->pluck('id')->toArray();

                $totalVideoSeconds = $videos->sum(function ($video) {
                    return $this->convertTimeToSeconds($video->video_duration);
                });

                $watchedDurations = UserContentWatchHistory::where('child_id', $data->id)
                    ->whereIn('video_content_id', $videoIds)
                    ->pluck('last_watched_duration')
                    ->toArray();

                $watchedSeconds = array_sum(
                    array_map([$this, 'convertTimeToSeconds'], $watchedDurations)
                );

                $percentage = $totalVideoSeconds > 0
                    ? min(100, round(($watchedSeconds / $totalVideoSeconds) * 100, 2))
                    : 0;

                $parentCategoryProgress[$categoryId] = [
                    'category_name' => $categoryName,
                    'percentage' => $percentage,
                    'total_video_duration' => gmdate('H:i:s', $totalVideoSeconds),
                ];
            }

            $data->categoryProgress = $parentCategoryProgress;

            return view('admin.schoolManagement.school_user_detail', compact('data', 'children'));
        }

        return abort(403, 'Unauthorized action.');
    }

    private function convertTimeToSeconds($time)
    {
        $parts = explode(':', $time);
        if (count($parts) === 2) {
            return ((int)$parts[0] * 60) + (int)$parts[1];
        } elseif (count($parts) === 3) {
            return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
        }
        return 0;
    }

    public function childrenProgress(Request $request, $user_id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, 2);

        if (!empty($pre) && $pre->is_modify === 'yes') {
            $data = User::where('id', $user_id)->first();

            if (!$data) {
                return abort(404, 'User not found');
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $children = User::where('parent_id', $user_id)->get();
            $categories = Category::all();
            $quizCategories = QuizCategory::where('status', 'active')
                ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
                ->get();

            $chartData = [];

            foreach ($children as $child) {
                // Video Progress
                $totalVideoSeconds = 0;
                $watchedSeconds = 0;

                foreach ($categories as $category) {
                    $videos = VideoContent::where('category_id', $category->id)
                        ->whereIn('user_type', ['child', 'both'])
                        ->get();

                    $videoIds = $videos->pluck('id')->toArray();

                    $totalVideoSeconds += $videos->sum(fn($video) => $this->convertTimeToSeconds($video->video_duration));

                    $query = UserContentWatchHistory::where('child_id', $child->id)
                        ->whereIn('video_content_id', $videoIds);

                    if ($startDate && $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                    $watchedDurations = $query->pluck('last_watched_duration')->toArray();

                    $watchedSeconds += array_sum(array_map([$this, 'convertTimeToSeconds'], $watchedDurations));
                }

                $videoPercentage = $totalVideoSeconds > 0
                    ? round(($watchedSeconds / $totalVideoSeconds) * 100, 2)
                    : 0;

                // Quiz Progress
                $totalMarks = 0;
                $obtainedMarks = 0;

                foreach ($quizCategories as $quizCategory) {
                    $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');

                    $totalMarks += QuizQuestion::whereIn('quiz_id', $quizIds)
                        ->where('status', 'active')
                        ->sum('marks');

                    $quizQuery = UserAttemptQuiz::where('user_id', $child->id)
                        ->whereIn('quiz_id', $quizIds);

                    if ($startDate && $endDate) {
                        $quizQuery->whereBetween('created_at', [$startDate, $endDate]);
                    }

                    $obtainedMarks += $quizQuery->sum('marks_obtained');
                }

                $quizPercentage = $totalMarks > 0
                    ? round(($obtainedMarks / $totalMarks) * 100, 2)
                    : 0;

                $chartData[] = [
                    'name' => $child->name,
                    'video_progress' => $videoPercentage,
                    'quiz_progress' => $quizPercentage,
                ];
            }

            return view('admin.schoolManagement.children_progress_chart', compact('data', 'chartData', 'startDate', 'endDate'));
        }

        return abort(403, 'Unauthorized action.');
    }






    public function allChildrenProgress(Request $request, $id)
    {
        $school = School::with('students')->findOrFail($id);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $ageFilter = $request->input('age_range'); // Age filter input (e.g., "11-14")

        $schoolParentIds = User::where('school_id', $id)
            ->where('user_type', 'parent')
            ->pluck('id');

        // ✅ Age Filter Logic based on DOB
        $childrenQuery = User::whereIn('parent_id', $schoolParentIds)
            ->where('user_type', 'child');

        if ($ageFilter) {
            $ages = explode('-', $ageFilter);
            $minAge = $ages[0];
            $maxAge = $ages[1];

            // DOB range calculate karna
            $startDateAge = \Carbon\Carbon::now()->subYears($maxAge + 1)->endOfDay();
            $endDateAge = \Carbon\Carbon::now()->subYears($minAge)->startOfDay();

            $childrenQuery->whereBetween('dob', [$startDateAge, $endDateAge]);
        }

        $children = $childrenQuery->get();
        $childrenIds = $children->pluck('id')->toArray();
        $totalChildCount = count($childrenIds);

        $quizCategories = QuizCategory::where('status', 'active')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->get();

        $chartData = [];
        foreach ($children as $child) {
            $totalMarks = 0;
            $obtainedMarks = 0;
            foreach ($quizCategories as $quizCategory) {
                $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');
                $totalMarks += QuizQuestion::whereIn('quiz_id', $quizIds)->where('status', 'active')->sum('marks');
                $quizQuery = UserAttemptQuiz::where('user_id', $child->id)->whereIn('quiz_id', $quizIds);

                if ($startDate && $endDate) {
                    $quizQuery->whereBetween('created_at', [
                        \Carbon\Carbon::parse($startDate)->startOfDay(),
                        \Carbon\Carbon::parse($endDate)->endOfDay()
                    ]);
                }
                $obtainedMarks += $quizQuery->sum('marks_obtained');
            }
            $quizPercentage = $totalMarks > 0 ? round(($obtainedMarks / $totalMarks) * 100, 2) : 0;
            $chartData[] = [
                'name' => $child->name,
                'school_code' => $school->school_code,
                'child_id' => $child->id,
                'quiz_progress' => $quizPercentage,
            ];
        }

        // Mood & Video Progress Logic (Original Logic kept intact)
        $moodQuery = DB::table('child_moods')->select('child_id', 'mood_name', DB::raw('COUNT(*) as count'))->whereIn('child_id', $childrenIds);
        if ($startDate && $endDate) {
            $moodQuery->whereBetween('created_at', [\Carbon\Carbon::parse($startDate)->startOfDay(), \Carbon\Carbon::parse($endDate)->endOfDay()]);
        }
        $moodChartData = $moodQuery->groupBy('child_id', 'mood_name')->get();

        $uniqueStudentsWhoSavedMood = DB::table('child_moods')->whereIn('child_id', $childrenIds)
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('created_at', [\Carbon\Carbon::parse($startDate)->startOfDay(), \Carbon\Carbon::parse($endDate)->endOfDay()]);
            })->distinct('child_id')->count('child_id');

        $avgMoodPercent = $totalChildCount > 0 ? round(($uniqueStudentsWhoSavedMood / $totalChildCount) * 100, 2) : 0;

        $moodLabels = $moodChartData->pluck('mood_name')->unique()->toArray();
        $moodCounts = [];
        $moodChildMap = [];
        foreach ($moodLabels as $label) {
            $count = 0;
            $names = [];
            foreach ($moodChartData as $mood) {
                if ($mood->mood_name == $label) {
                    $count += $mood->count;
                    $names[] = $children->where('id', $mood->child_id)->first()->name;
                }
            }
            $moodCounts[] = $count;
            $moodChildMap[$label] = implode(', ', $names);
        }

        $categories = Category::all();
        // $videoChartData = [];
        // $age = Carbon::parse($child->dob)->age;
        // foreach ($children as $child) {
        //     foreach ($categories as $category) {
        //         $videos = VideoContent::where('category_id', $category->id)->whereIn('user_type', ['child', 'both'])->get();
        //         $videoIds = $videos->pluck('id')->toArray();
        //         $totalVideoSeconds = $videos->sum(fn($v) => $this->convertTimeToSeconds($v->video_duration));
        //         $watchedDurations = UserContentWatchHistory::where('child_id', $child->id)->whereIn('video_content_id', $videoIds);
        //         if ($startDate && $endDate) {
        //             $watchedDurations->whereBetween('created_at', [\Carbon\Carbon::parse($startDate)->startOfDay(), \Carbon\Carbon::parse($endDate)->endOfDay()]);
        //         }
        //         $watchedSeconds = $watchedDurations->pluck('last_watched_duration')->sum(fn($t) => $this->convertTimeToSeconds($t));

        //         $videoChartData[] = [
        //             'child_name' => $child->name,
        //             'category_name' => $category->category_name,
        //             'percentage' => $totalVideoSeconds > 0 ? min(100, round(($watchedSeconds / $totalVideoSeconds) * 100, 2)) : 0,
        //             'age_group' => $age >= 11 && $age <= 14 ? '11-14' : '15-18',
        //         ];
        //     }
        // }

       // ========== Age-Grouped Video Bar Chart Data ==========
        $ageGroups = [
            '11-14' => ['min' => 11, 'max' => 14],
            '15-18' => ['min' => 15, 'max' => 18],
        ];

        $videoBarData = [];
        foreach ($categories as $category) {
            $entry = ['category_name' => $category->category_name];

            foreach ($ageGroups as $label => $range) {
                $startDob = \Carbon\Carbon::now()->subYears($range['max'] + 1)->endOfDay();
                $endDob   = \Carbon\Carbon::now()->subYears($range['min'])->startOfDay();

                $ageChildren = User::whereIn('parent_id', $schoolParentIds)
                    ->where('user_type', 'child')
                    ->whereBetween('dob', [$startDob, $endDob])
                    ->get();

                $videos = VideoContent::where('category_id', $category->id)
                    ->whereIn('user_type', ['child', 'both'])
                    ->get();

                $videoIds  = $videos->pluck('id')->toArray();
                $totalSec  = $videos->sum(fn($v) => $this->convertTimeToSeconds($v->video_duration));
                $childCount = count($ageChildren);
                $totalWatched = 0;

                foreach ($ageChildren as $c) {
                    $q = UserContentWatchHistory::where('child_id', $c->id)
                        ->whereIn('video_content_id', $videoIds);

                    if ($startDate && $endDate) {
                        $q->whereBetween('created_at', [
                            \Carbon\Carbon::parse($startDate)->startOfDay(),
                            \Carbon\Carbon::parse($endDate)->endOfDay(),
                        ]);
                    }

                    $watched = $q->pluck('last_watched_duration')
                        ->sum(fn($t) => $this->convertTimeToSeconds($t));

                    $totalWatched += $totalSec > 0
                        ? min(100, round(($watched / $totalSec) * 100, 2))
                        : 0;
                }

                $entry[$label] = $childCount > 0
                    ? round($totalWatched / $childCount, 2)
                    : 0;
            }

            $videoBarData[] = $entry;
        }

        // ========== Age-Grouped Quiz Bar Chart Data ==========
        $quizBarData = [];
        foreach ($quizCategories as $quizCategory) {
            $entry = ['category_name' => $quizCategory->category_name];
            $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');
            $totalMarksForCat = QuizQuestion::whereIn('quiz_id', $quizIds)
                ->where('status', 'active')
                ->sum('marks');

            foreach ($ageGroups as $label => $range) {
                $startDob = \Carbon\Carbon::now()->subYears($range['max'] + 1)->endOfDay();
                $endDob   = \Carbon\Carbon::now()->subYears($range['min'])->startOfDay();

                $ageChildren = User::whereIn('parent_id', $schoolParentIds)
                    ->where('user_type', 'child')
                    ->whereBetween('dob', [$startDob, $endDob])
                    ->get();

                $childCount   = count($ageChildren);
                $totalObtained = 0;

                foreach ($ageChildren as $c) {
                    $quizQuery = UserAttemptQuiz::where('user_id', $c->id)
                        ->whereIn('quiz_id', $quizIds);

                    if ($startDate && $endDate) {
                        $quizQuery->whereBetween('created_at', [
                            \Carbon\Carbon::parse($startDate)->startOfDay(),
                            \Carbon\Carbon::parse($endDate)->endOfDay(),
                        ]);
                    }

                    $obtained = $quizQuery->sum('marks_obtained');
                    $totalObtained += $totalMarksForCat > 0
                        ? min(100, round(($obtained / $totalMarksForCat) * 100, 2))
                        : 0;
                }

                $entry[$label] = $childCount > 0
                    ? round($totalObtained / $childCount, 2)
                    : 0;
            }

            $quizBarData[] = $entry;
        }



        $uniqueStudentsWhoWatchedVideo = DB::table('user_content_watch_histories')->whereIn('child_id', $childrenIds)
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                return $query->whereBetween('updated_at', [\Carbon\Carbon::parse($startDate)->startOfDay(), \Carbon\Carbon::parse($endDate)->endOfDay()]);
            })->distinct('child_id')->count('child_id');

        $averageVideoProgress = $totalChildCount > 0 ? round(($uniqueStudentsWhoWatchedVideo / $totalChildCount) * 100, 2) : 0;

        return view('admin.schoolManagement.school_children_progress', [
            'school' => $school,
            'chartData' => array_values($chartData),
            'moodLabels' => array_values($moodLabels),
            'moodCounts' => array_values($moodCounts),
            'moodChildMap' => $moodChildMap,
            // 'videoChartData' => array_values($videoChartData),
            'videoChartData' => [],
            'averageVideoProgress' => $averageVideoProgress,
            'avgMoodPercent' => $avgMoodPercent,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'ageFilter' => $ageFilter,
            'videoBarData' => $videoBarData,
            'quizBarData' => $quizBarData,
        ]);
    }
}
