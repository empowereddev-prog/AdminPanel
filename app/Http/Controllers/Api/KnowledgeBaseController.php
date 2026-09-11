<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Models\VideoContent;
use App\Models\Category;
use App\Models\User;
use App\Models\UserContentWatchHistory;
use App\Models\UserLikedVideo;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesApiUser;

    // public function videoContent1(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $perPage = (int) ($request->per_page ?? 10);
    //     $page = (int) ($request->page ?? 1);
    //     $search = $request->search;
    //     $filter = $request->filter ?? 'all';
    //     $categoryId = $request->category_id;

    //     if ($categoryId && $request->user_id) {
    //         $child = User::findOrFail($request->user_id);
    //         $childAge = \Carbon\Carbon::parse($child->dob)->age;
    //         $parent = User::find($child->parent_id);
    //         $parentSchoolIds = $parent ? (array) $parent->school_id : [];
    //         // Base query
    //         // $query = VideoContent::where('category_id', $categoryId)
    //         //     // ->where('user_type', 'child')
    //         //     ->whereIn('user_type', ['child', 'both'])
    //         //     ->where('status', 'active');
    //        $query = VideoContent::where('status', 'active')
    //    ->whereIn('user_type', ['child', 'both'])
    //    ->where('category_id', $categoryId)
    // ->where(function ($q) use ($parentSchoolIds) {

    //   // ✅ Handle NULL + empty JSON properly
    //    $q->where(function ($sub) {
    //     $sub->whereNull('school_id')
    //         ->orWhereRaw("JSON_LENGTH(COALESCE(school_id, '[]')) = 0");
    //    });

    // // ✅ School specific
    //   if (!empty($parentSchoolIds)) {
    //     $q->orWhere(function ($inner) use ($parentSchoolIds) {
    //         foreach ($parentSchoolIds as $id) {
    //             $inner->orWhereJsonContains('school_id', $id);
    //         }
    //     });
    //   }

    //    });
    //         // Apply search
    //         // if ($search) {
    //         //     $query->where(function ($q) use ($search) {
    //         //         $q->where('title', 'like', "%$search%")
    //         //             ->orWhere('featured_key', 'like', "%$search%")
    //         //             ->orWhere('title_chinese', 'like', "%$search%")
    //         //             ->orWhere('description', 'like', "%$search%")
    //         //             ->orWhere('description_chinese', 'like', "%$search%");
    //         //     });
    //         // }
    //         if ($search) {
    //             $query->where(function ($q) use ($search) {

    //                 $q->where('title', 'like', "%{$search}%")
    //                     ->orWhere('title_chinese', 'like', "%{$search}%")
    //                     ->orWhere('description', 'like', "%{$search}%")
    //                     ->orWhere('description_chinese', 'like', "%{$search}%")

    //                     // 🔹 featured_key FIX (full + partial)
    //                     ->orWhereRaw(
    //                         "LOWER(TRIM(featured_key)) LIKE ?",
    //                         ['%' . strtolower(trim($search)) . '%']
    //                     );
    //             });
    //         }

    //         $userId = $request->user_id;
    //         // Apply filters
    //         // switch ($filter) {
    //         //     case 'popular':
    //         //         $query->where('is_featured', 'yes');
    //         //         break;
    //         //     case 'newest':
    //         //         $query->orderBy('created_at', 'desc');
    //         //         break;
    //         //     case 'oldest':
    //         //         $query->orderBy('created_at', 'asc');
    //         //         break;
    //         //     case 'favourite':
    //         //         $query->whereIn('video_contents.id', function ($q) use ($userId, $categoryId) {
    //         //             $q->select('video_id')
    //         //                 ->from('user_liked_videos')
    //         //                 ->join('video_contents', 'user_liked_videos.video_id', '=', 'video_contents.id')
    //         //                 ->where('user_liked_videos.user_id', $userId)
    //         //                 ->where('user_liked_videos.type', 'favourite')
    //         //                 ->where('video_contents.category_id', $categoryId);
    //         //         });
    //         //         break;
    //         //     case 'all':
    //         //     default:
    //         //         // No extra filter
    //         //         break;
    //         // }

    //         switch ($filter) {
    //             case 'newest':
    //                 $query->orderBy('created_at', 'desc');
    //                 break;

    //             case 'favrits':
    //                 $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
    //                     $join->on('video_contents.id', '=', 'ulv.video_id')
    //                         ->where('ulv.type', 'favourite')
    //                         ->where('ulv.user_id', $userId);
    //                 })
    //                     ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
    //                     ->groupBy('video_contents.id')
    //                     ->having('favourite_count', '>', 0)
    //                     ->orderByDesc('favourite_count');
    //                 break;

    //             case 'tranding':
    //                 $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
    //                     $join->on('video_contents.id', '=', 'ulv.video_id')
    //                         ->where('ulv.type', 'like')
    //                         ->where('ulv.user_id', $userId);
    //                 })
    //                     ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
    //                     ->groupBy('video_contents.id')
    //                     ->having('like_count', '>', 0)
    //                     ->orderByDesc('like_count');
    //                 break;

    //             case 'all':
    //             default:
    //                 $query->inRandomOrder();   // ⭐ RANDOM VIDEOS HERE
    //                 break;
    //         }



    //         // Get results as collection to filter by age
    //         $videoContent = $query->get();
    //         // Filter based on age
    //         $filtered = $videoContent->filter(function ($video) use ($childAge) {
    //             if (strpos($video->age, '-') !== false) {
    //                 list($minAge, $maxAge) = explode('-', $video->age);
    //                 return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
    //             }
    //             return false;
    //         })->values(); // reset keys after filter

    //         // $total = $filtered->count();

    //         // if ($total === 0) {
    //         //     return response()->json([
    //         //         'status' => false,
    //         //         'message' => 'No data found.',
    //         //         'data' => [],
    //         //     ], 200);
    //         // }

    //         // $paginatedItems = $filtered->forPage($page, $perPage)->values();

    //         // $formattedData = $paginatedItems->map(function ($video) use ($language) {
    //         //     $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
    //         //     $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');
    //         //     // Set title and description based on language
    //         //     $video->title = $language === 'chinese'
    //         //         ? ($video->title_chinese ?? $video->title)
    //         //         : $video->title;

    //         //     $video->description = $language === 'chinese'
    //         //         ? ($video->description_chinese ?? $video->description)
    //         //         : $video->description;

    //         //     $video->category_name = $language === 'chinese'
    //         //         ? (Category::where('id', $video->category_id)->value('category_name_chinese') ?? Category::where('id', $video->category_id)->value('category_name'))
    //         //         : Category::where('id', $video->category_id)->value('category_name');

    //         //     unset($video->title_chinese, $video->description_chinese);

    //         //     $userId = Auth::id();
    //         //     $likeStatus = UserLikedVideo::where('user_id', $userId)
    //         //         ->where('video_id', $video->id)
    //         //         ->get()
    //         //         ->pluck('type')
    //         //         ->toArray();

    //         //     $video->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
    //         //     $video->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
    //         //     $video->is_heart = in_array('favourite', $likeStatus) ? 'yes' : 'no';
    //         //     return $video;
    //         // });
    //         $total = $filtered->count();

    //         if ($total === 0) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'No data found.',
    //                 'data' => [],
    //             ], 200);
    //         }

    //         $paginatedItems = $filtered->forPage($page, $perPage)->values();

    //         $formattedData = $paginatedItems->map(function ($video) use ($language) {

    //             $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
    //             $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

    //             // Set title based on language
    //             $video->title = $language === 'chinese'
    //                 ? ($video->title_chinese ?? $video->title)
    //                 : $video->title;

    //             // Set description based on language
    //             $video->description = $language === 'chinese'
    //                 ? ($video->description_chinese ?? $video->description)
    //                 : $video->description;

    //             // Category name
    //             $video->category_name = $language === 'chinese'
    //                 ? (
    //                     Category::where('id', $video->category_id)->value('category_name_chinese')
    //                     ?? Category::where('id', $video->category_id)->value('category_name')
    //                 )
    //                 : Category::where('id', $video->category_id)->value('category_name');

    //             unset($video->title_chinese, $video->description_chinese);

    //             $userId = Auth::id();

    //             $likeStatus = UserLikedVideo::where('user_id', $userId)
    //                 ->where('video_id', $video->id)
    //                 ->get()
    //                 ->pluck('type')
    //                 ->toArray();

    //             $video->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
    //             $video->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
    //             $video->is_heart = in_array('favourite', $likeStatus) ? 'yes' : 'no';

    //             return $video;
    //         });
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Data fetched successfully!',
    //             'data' => [
    //                 'data' => $formattedData,
    //                 'total' => $total,
    //                 'per_page' => (int)$perPage,
    //                 'current_page' => (int)$page,
    //                 'last_page' => ceil($total / $perPage)
    //             ]
    //         ], 200);
    //     }

    //     return response()->json([
    //         'status' => false,
    //         'message' => 'Data not found',
    //         'data' => null,
    //     ], 200);
    // }

    public function videoContent(Request $request)
    {
        $language   = $request->language ?? 'english';
        $perPage    = (int) ($request->per_page ?? 10);
        $page       = (int) ($request->page ?? 1);
        $search     = $request->search;
        $filter     = $request->filter ?? 'all';
        $categoryId = $request->category_id;

        if ($request->user_id) {

            $child = $this->resolveTargetUser($request, 'user_id');

            if (!$child) {
                return $this->unauthorisedTargetResponse($request->language ?? 'english');
            }

            $childAge = \Carbon\Carbon::parse($child->dob)->age;

            $parent = User::find($child->parent_id);

            // =======================
            // FORMAT SCHOOL IDS
            // =======================
            $parentSchoolIds = [];

            if ($parent && $parent->school_id !== null) {

                if (is_array($parent->school_id)) {
                    $parentSchoolIds = $parent->school_id;
                } elseif (is_string($parent->school_id)) {
                    $decoded = json_decode($parent->school_id, true);
                    $parentSchoolIds = is_array($decoded) ? $decoded : [(int)$parent->school_id];
                } elseif (is_int($parent->school_id)) {
                    $parentSchoolIds = [$parent->school_id];
                }

                $parentSchoolIds = array_map('intval', $parentSchoolIds);
            }

            // =======================
            // MAIN QUERY (UPDATED)
            // =======================
            // $query = VideoContent::with('category')
            //     ->useWritePdo() // 🔥 FIX: Always fetch latest data
            //     ->where('status', 'active')
            //     ->whereIn('user_type', ['child', 'both'])
            //     ->where('category_id', $categoryId)

            //     ->where(function ($q) use ($parentSchoolIds) {

            //         // General videos
            //         $q->whereNull('school_id')
            //           ->orWhereJsonLength('school_id', 0);

            //         // School specific videos
            //         if (!empty($parentSchoolIds)) {
            //             $q->orWhere(function ($inner) use ($parentSchoolIds) {
            //                 foreach ($parentSchoolIds as $id) {
            //                     $inner->orWhereJsonContains('school_id', $id);
            //                 }
            //             });
            //         }

            //     });

            $query = VideoContent::with('category')
                ->useWritePdo()
                ->where('status', 'active')
                ->whereIn('user_type', ['child', 'both'])

                // CRITICAL FIX: Exclude wellness category mood videos from standard API section
                ->where(function($q) {
                    $q->whereNull('mood')
                      ->orWhere('mood', '!=', 'mood');
                })

                ->when($categoryId !== null, function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                })

                ->where(function ($q) use ($parentSchoolIds) {

                    $q->whereNull('school_id')
                        ->orWhereJsonLength('school_id', 0);

                    if (!empty($parentSchoolIds)) {
                        $q->orWhere(function ($inner) use ($parentSchoolIds) {
                            foreach ($parentSchoolIds as $id) {
                                $inner->orWhereJsonContains('school_id', $id);
                            }
                        });
                    }
                });

            // =======================
            // SEARCH
            // =======================
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('title_chinese', 'like', "%{$search}%")
                        ->orWhereRaw("LOWER(TRIM(featured_key)) LIKE ?", ['%' . strtolower(trim($search)) . '%']);
                });
            }

            $userId = $child->id;

            // =======================
            // FILTER
            // =======================
            switch ($filter) {

                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;

                case 'favrits':
                    $query->leftJoin('user_liked_videos as ulv', function ($join) use ($userId) {
                        $join->on('video_contents.id', '=', 'ulv.video_id')
                            ->where('ulv.type', 'favourite')
                            ->where('ulv.user_id', $userId);
                    })->select('video_contents.*');
                    break;

                case 'tranding':
                    $query->leftJoin('user_liked_videos as ulv', function ($join) use ($userId) {
                        $join->on('video_contents.id', '=', 'ulv.video_id')
                            ->where('ulv.type', 'like')
                            ->where('ulv.user_id', $userId);
                    })->select('video_contents.*');
                    break;

                default:
                    $query->orderBy('id', 'desc');
                    break;
            }

            $videoContent = $query->get();

            // =======================
            // AGE FILTER
            // =======================
            $filtered = $videoContent->filter(function ($video) use ($childAge) {

                if (!$video->age || trim($video->age) === '') {
                    return true;
                }

                if (preg_match('/^\d+\-\d+$/', $video->age)) {
                    [$min, $max] = explode('-', $video->age);
                    return $childAge >= (int)$min && $childAge <= (int)$max;
                }

                return false;
            })->values();

            $total = $filtered->count();

            if ($total === 0) {
                return ApiResponse::error('No data found', 200, null, []);
            }

            // =======================
            // PAGINATION
            // =======================
            $paginatedItems = $filtered->forPage($page, $perPage)->values();

            // =======================
            // FORMAT RESPONSE
            // =======================
            $formattedData = $paginatedItems->map(function ($video) use ($language, $userId) {

                $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
                $video->thumbnail  = getImagePathUrl($video->thumbnail, 'assets/images');

                $video->title = $language === 'chinese'
                    ? ($video->title_chinese ?? $video->title)
                    : $video->title;

                $video->description = $language === 'chinese'
                    ? ($video->description_chinese ?? $video->description)
                    : $video->description;

                $video->category_name = $video->category
                    ? ($language === 'chinese'
                        ? ($video->category->category_name_chinese ?? '')
                        : ($video->category->category_name ?? ''))
                    : '';

                $likeStatus = UserLikedVideo::where('user_id', $userId)
                    ->where('video_id', $video->id)
                    ->pluck('type')
                    ->toArray();

                $video->is_like  = in_array('like', $likeStatus) ? 'yes' : 'no';
                $video->is_heart = in_array('favourite', $likeStatus) ? 'yes' : 'no';

                return $video;
            });

            return ApiResponse::success([
                'data' => $formattedData,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ], 'Data fetched successfully');
        }

        // Not migrated: the contract here is data => null, which ApiResponse
        // renders as {} - a type change for the shipped app. Convert only
        // alongside a client release. @envelope-exempt
        return response()->json([
            'status' => false,
            'message' => 'Invalid parameters',
            'data' => null
        ]);
    }



    // Video Content for quiz
    public function videoContentQuiz(Request $request)
    {
        $language = $request->language ?? 'english';
        $perPage = (int) ($request->per_page ?? 10);
        $page = (int) ($request->page ?? 1);
        $search = $request->search;
        $filter = $request->filter ?? 'all';

        $categoryId = $request->category_id;

        $userId = auth()->user()->id;

        if ($categoryId && $userId) {
            $child = User::findOrFail($userId);
            $childAge = Carbon::parse($child->dob)->age;

            // Base query
            $query = VideoContent::where('category_id', $categoryId)
                // ->where('user_type', 'child')
                ->whereIn('user_type', ['child', 'both'])
                ->where('status', 'active');

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%")
                        ->orWhere('featured_key', 'like', "%$search%")
                        ->orWhere('title_chinese', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%")
                        ->orWhere('description_chinese', 'like', "%$search%");
                });
            }


            switch ($filter) {
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;

                case 'favrits':
                    $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                        $join->on('video_contents.id', '=', 'ulv.video_id')
                            ->where('ulv.type', 'favourite')
                            ->where('ulv.user_id', $userId);
                    })
                        ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
                        ->groupBy('video_contents.id')
                        ->having('favourite_count', '>', 0)
                        ->orderByDesc('favourite_count');
                    break;

                case 'tranding':
                    $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                        $join->on('video_contents.id', '=', 'ulv.video_id')
                            ->where('ulv.type', 'like')
                            ->where('ulv.user_id', $userId);
                    })
                        ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
                        ->groupBy('video_contents.id')
                        ->having('like_count', '>', 0)
                        ->orderByDesc('like_count');
                    break;

                case 'all':
                default:
                    // No extra filters
                    break;
            }
            // Get results as collection to filter by age
            $videoContent = $query->take(5)->get();
            // Filter based on age
            $filtered = $videoContent->filter(function ($video) use ($childAge) {
                if (strpos($video->age, '-') !== false) {
                    list($minAge, $maxAge) = explode('-', $video->age);
                    return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
                }
                return false;
            })->values(); // reset keys after filter

            $total = $filtered->count();
            if ($total === 0) {
                return ApiResponse::error('No data found.', 200, null, []);
            }
            $paginatedItems = $filtered->forPage($page, $perPage);

            $formattedData = $paginatedItems->map(function ($video) use ($language) {
                $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
                $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');
                // Set title and description based on language
                $video->title = $language === 'chinese'
                    ? ($video->title_chinese ?? $video->title)
                    : $video->title;

                $video->description = $language === 'chinese'
                    ? ($video->description_chinese ?? $video->description)
                    : $video->description;

                $video->category_name = $language === 'chinese'
                    ? (Category::where('id', $video->category_id)->value('category_name_chinese') ?? Category::where('id', $video->category_id)->value('category_name'))
                    : Category::where('id', $video->category_id)->value('category_name');

                unset($video->title_chinese, $video->description_chinese);

                $userId = Auth::id();
                $likeStatus = UserLikedVideo::where('user_id', $userId)
                    ->where('video_id', $video->id)
                    ->get()
                    ->pluck('type')
                    ->toArray();

                $video->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
                $video->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
                $video->is_heart = in_array('favourite', $likeStatus) ? 'yes' : 'no';
                return $video;
            });

            return ApiResponse::success([
                'data' => $formattedData,
                'total' => $total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage),
            ], 'Data fetched successfully!');
        }

        // Not migrated: the contract here is data => null, which ApiResponse
        // renders as {} - a type change for the shipped app. Convert only
        // alongside a client release. @envelope-exempt
        return response()->json([
            'status' => false,
            'message' => 'Data not found',
            'data' => null,
        ], 200);
    }


    // public function userContentWatchHistories(Request $request)
    // {
    //     $detail = UserContentWatchHistory::where('child_id', $request->user_id)->where('video_content_id', $request->video_content_id)->latest()->first();
    //     if ($detail) {
    //         $detail->delete();
    //     }
    //     $userContentWatchHistory =  UserContentWatchHistory::create([
    //         'child_id' => $request->user_id,
    //         'video_content_id' => $request->video_content_id,
    //         'last_watched_duration' => $request->last_watched_duration,
    //         'total_video_duration' => $request->total_video_duration,
    //         'is_completed' => $request->is_video_completed == true ? 'yes' : 'no',
    //     ]);
    //     $points = VideoContent::where('id', $request->video_content_id)->value('points');
    //     if ($request->is_video_completed == true) {
    //         $user = User::where('id', $request->user_id)->first();
    //         $user->loyalty_points += $points;
    //         $user->save();
    //     }
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'data' => $userContentWatchHistory
    //     ], 200);
    // }
    public function userContentWatchHistories(Request $request)
    {
        // 1. Validation check (Hamesha acchi practice hai)
        if (!$request->user_id || !$request->video_content_id) {
            return ApiResponse::error('Missing IDs', 400);
        }

        // The watch history and the loyalty points below belong to this user, so
        // the id must be the caller or their own child - not any id they send.
        $targetId = $this->resolveTargetUserId($request, 'user_id');

        if (!$targetId) {
            return $this->unauthorisedTargetResponse($request->language ?? 'english');
        }

        $history = UserContentWatchHistory::where('child_id', $targetId)
            ->where('video_content_id', $request->video_content_id)
            ->first();

        $wasCompletedBefore = $history && $history->is_completed === 'yes';
        $isNowCompleted = ($request->is_video_completed == 'yes' || $request->is_video_completed === true);

        if ($history) {
            $history->update([
                'last_watched_duration' => $request->last_watched_duration,
                'total_video_duration' => $request->total_video_duration,
                'is_completed' => $isNowCompleted ? 'yes' : 'no',
            ]);
        } else {
            $history = UserContentWatchHistory::create([
                'child_id' => $targetId,
                'video_content_id' => $request->video_content_id,
                'last_watched_duration' => $request->last_watched_duration,
                'total_video_duration' => $request->total_video_duration,
                'is_completed' => $isNowCompleted ? 'yes' : 'no',
            ]);
        }

        // ✅ Fix: Add points ONLY when video becomes completed first time
        if (!$wasCompletedBefore && $isNowCompleted) {
            $points = VideoContent::where('id', $request->video_content_id)->value('points');

            // Yahan fix hai: Agar points null hain toh 0 lo, aur ensure karo ki ye numeric ho
            $pointsToAdd = is_numeric($points) ? (int)$points : 0;

            if ($pointsToAdd > 0) {
                User::where('id', $targetId)->increment('loyalty_points', $pointsToAdd);
            }
        }

        return ApiResponse::success($history, 'Data saved successfully!', 200);
    }
    // public function userContentWatchHistories(Request $request)
    // {
    //     $history = UserContentWatchHistory::where('child_id', $request->user_id)
    //         ->where('video_content_id', $request->video_content_id)
    //         ->first();

    //     $wasCompletedBefore = $history && $history->is_completed === 'yes';
    //     $isNowCompleted = ($request->is_video_completed == 'yes' || $request->is_video_completed === true);
    //     if ($history) {
    //         // update existing
    //         $history->update([
    //             'last_watched_duration' => $request->last_watched_duration,
    //             'total_video_duration' => $request->total_video_duration,
    //             'is_completed' => $isNowCompleted ? 'yes' : 'no',
    //         ]);
    //     } else {
    //         // create new
    //         $history = UserContentWatchHistory::create([
    //             'child_id' => $request->user_id,
    //             'video_content_id' => $request->video_content_id,
    //             'last_watched_duration' => $request->last_watched_duration,
    //             'total_video_duration' => $request->total_video_duration,
    //             'is_completed' => $isNowCompleted ? 'yes' : 'no',
    //         ]);
    //     }

    //     // ✅ add points ONLY when video becomes completed first time
    //     if (!$wasCompletedBefore && $request->is_video_completed) {
    //         $points = VideoContent::where('id', $request->video_content_id)->value('points');

    //         User::where('id', $request->user_id)
    //             ->increment('loyalty_points', $points);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data saved successfully!',
    //         'data' => $history
    //     ], 200);
    // }



    public function fetchVideoWatchStatus(Request $request)
    {
        $category_id = $request->category_id;
        $language = $request->language;
        $child_id = $this->resolveTargetUserId($request, 'user_id');

        if (!$child_id) {
            return $this->unauthorisedTargetResponse($language ?? 'english');
        }
        $category_name = Category::where('id', $category_id)->value('category_name');
        $video_content_points = VideoContent::where('category_id', $category_id)
            // ->where('user_type', 'child')
            ->whereIn('user_type', ['child', 'both'])
            ->sum('points');

        $video_durations = VideoContent::where('category_id', $category_id)
            // ->where('user_type', 'child')
            ->whereIn('user_type', ['child', 'both'])
            ->pluck('video_duration')->toArray();

        // Convert video time to total seconds
        $total_time = 0;
        foreach ($video_durations as $time) {
            $parts = explode(':', $time);
            if (count($parts) == 2) { // MM:SS format
                $minutes = (int) $parts[0];
                $seconds = (int) $parts[1];
                $total_time += ($minutes * 60) + $seconds;
            } elseif (count($parts) == 3) { // HH:MM:SS format
                $hours = (int) $parts[0];
                $minutes = (int) $parts[1];
                $seconds = (int) $parts[2];
                $total_time += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }
        $video_contents = VideoContent::where('category_id', $category_id)
            // ->where('user_type', 'child')
            ->whereIn('user_type', ['child', 'both'])
            ->get();
        $video_content_ids = $video_contents->pluck('id')->toArray();
        // dd($total_time);
        $last_watched_duration = UserContentWatchHistory::where('child_id', $child_id)->whereIn('video_content_id', $video_content_ids)->pluck('last_watched_duration')->toArray();
        $total_last_watched_duration_time = 0;
        foreach ($last_watched_duration as $last_watched) {
            $last_watched_duration_parts = explode(':', $last_watched);
            // dd($last_watched_duration_parts);
            if (count($last_watched_duration_parts) == 2) { // MM:SS format
                $minutes = (int) $last_watched_duration_parts[0];
                $seconds = (int) $last_watched_duration_parts[1];
                $total_last_watched_duration_time += ($minutes * 60) + $seconds;
            } elseif (count($last_watched_duration_parts) == 3) { // HH:MM:SS format
                $hours = (int) $last_watched_duration_parts[0];
                $minutes = (int) $last_watched_duration_parts[1];
                $seconds = (int) $last_watched_duration_parts[2];
                $total_last_watched_duration_time += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }
        $remaining_seconds = max(0, $total_time - $total_last_watched_duration_time);

        $watched_percentage = ($total_time > 0) ? round(($total_last_watched_duration_time / $total_time) * 100, 2) : 0;

        $formatted_total_time = gmdate("H:i:s", $total_time);
        $formatted_remaining_time = gmdate("H:i:s", $remaining_seconds);

        return ApiResponse::success(
            [
                'total_points' => (int) $video_content_points,
                'category' => $category_name,
                'total_video_duration' => $formatted_total_time,
                'remaining_video_duration' => $formatted_remaining_time,
                'watched_percentage' => (int)$watched_percentage,
                'video_durations' => $video_durations,
            ],
            $language == 'chinese' ? '视频内容获取成功！' : 'Video content fetched successfully!'
        );
    }
    public function videoContentdetails(Request $request)
    {
        $videoId = $request->video_id ?? $request->id;
        $language = $request->language ?? 'english';
        $deepLink = app(\App\Services\DeepLinkService::class)->resolve('podcast', $videoId, auth()->user(), false);
        if ($deepLink['status'] !== \App\Services\DeepLinkService::STATUS_OK) {
            // @envelope-exempt. Not migrated: data => null (see above), and the deep-link keys
            // deeplink_status/canonical_url are read by the app at top level.
            // The snapshot pins this exact shape as video-content-details.
            return response()->json([
                'status' => false,
                'deeplink_status' => $deepLink['status'],
                'message' => $deepLink['status'] === 'forbidden_role'
                    ? 'This content is not available for your account.'
                    : ($deepLink['status'] === 'subscription_required'
                        ? 'An active subscription is required.'
                        : 'Video content not found.'),
                'canonical_url' => $deepLink['canonical_url'],
                'data' => null
            ], $deepLink['http_status']);
        }

        $viewerId = $this->resolveTargetUserId($request, 'user_id');

        if (!$viewerId) {
            return $this->unauthorisedTargetResponse($language);
        }

        $video_content = VideoContent::where('id', $videoId)->where('status', 'active')->first();
        $last_watched_duration = UserContentWatchHistory::where('child_id', $viewerId)->where('video_content_id', $videoId)->value('last_watched_duration');
        if ($video_content) {
            $video_content['title'] = $language === 'english' ? $video_content->title : $video_content->title_chinese;
            $video_content['description'] = $language === 'english' ? $video_content->description : $video_content->description_chinese;
            $video_content['video_link'] =  getImagePathUrl($video_content->video_link, 'assets/video');
            $video_content['watched_duration'] = $last_watched_duration ?? "00:00";
            $is_video_completed =  UserContentWatchHistory::where('child_id', $viewerId)->where('video_content_id', $videoId)->value('is_completed');
            $video_content['is_video_completed'] = $is_video_completed;

            $likeStatus = UserLikedVideo::where('user_id', $viewerId)
                ->where('video_id', $videoId)
                ->get()
                ->pluck('type')
                ->toArray();

            $video_content->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
            $video_content->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
            $video_content->is_heart = in_array('favourite', $likeStatus) ? 'yes' : 'no';
            $video_content->category_name = $video_content->category->category_name;
            $video_content->canonical_url = $deepLink['canonical_url'];
            return ApiResponse::success($video_content, 'Video content fetched successfully!', 200);
        } else {
            // Not migrated: data => null, plus a top-level deeplink_status. @envelope-exempt
            return response()->json([
                'status' => false,
                'deeplink_status' => 'not_found',
                'message' => 'Video content not found.',
                'data' => null
            ], 404);
        }
    }
    public function videoContentforparent1(Request $request)
    {


        $language = $request->language ?? 'english';
        $search = $request->search ?? null;
        $filter = $request->filter ?? 'all'; // default filter
        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $category_id = $request->category_id;

        $user = auth()->user();
        $userId = $user->id;
        $userType = $user->user_type;

        // Get child data
        // $child = User::findOrFail($request->user_id);

        // Base query
        if ($userType == 'parent') {
            $query = VideoContent::whereIn('user_type', ['parent', 'both'])
                ->where('status', 'active')
                // CRITICAL FIX:
                ->where(function($q) {
                    $q->whereNull('mood')->orWhere('mood', '!=', 'mood');
                });
        }
        if ($userType == 'child') {
            $query = VideoContent::whereIn('user_type', ['child', 'both'])
                ->where('status', 'active')
                // CRITICAL FIX: Duplication Issue
                ->where(function($q) {
                    $q->whereNull('mood')->orWhere('mood', '!=', 'mood');
                });
        }
        //  Category filter apply
        if (!empty($category_id)) {
            $query->where('category_id', $category_id);
        }

        // Apply search if provided
        // if ($search) {
        //     $query->where(function ($q) use ($search) {
        //         $q->where('title', 'like', "%$search%")
        //             ->orWhere('featured_key', 'like', "%$search%")
        //             ->orWhere('title_chinese', 'like', "%$search%");
        //         // ->orWhere('description', 'like', "%$search%")
        //         // ->orWhere('description_chinese', 'like', "%$search%");
        //     });
        // }

        if ($search) {
            $search = strtolower(trim($search));
            // optional but recommended: minimum length
            if (strlen($search) < 3) {
                $query->whereRaw('1 = 0'); // no results
            } else {

                $query->where(function ($q) use ($search) {

                    $q->whereRaw("LOWER(title) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(title_chinese) LIKE ?", ["%{$search}%"]);

                    // 🔥 featured_key ONLY if not null
                    $q->orWhere(function ($qq) use ($search) {
                        $qq->whereNotNull('featured_key')
                            ->whereRaw("LOWER(featured_key) LIKE ?", ["%{$search}%"]);
                    });
                });
            }
        }


        // Apply filters
        // switch ($filter) {
        //     case 'popular':
        //         $query->where('is_featured', 'yes');
        //         break;
        //     case 'newest':
        //         $query->orderBy('created_at', 'desc');
        //         break;
        //     case 'oldest':
        //         $query->orderBy('created_at', 'asc');
        //         break;
        //     case 'all':
        //     default:
        //         // no additional filtering
        //         break;
        // }

        switch ($filter) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'favrits':
                $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'favourite')
                        ->where('ulv.user_id', $userId);
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
                    ->groupBy('video_contents.id')
                    ->having('favourite_count', '>', 0)
                    ->orderByDesc('favourite_count');
                break;

            case 'tranding':
                $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'like')
                        ->where('ulv.user_id', $userId);
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
                    ->groupBy('video_contents.id')
                    ->having('like_count', '>', 0)
                    ->orderByDesc('like_count');
                break;

            case 'all':
            default:
                // No extra filters
                break;
        }

        // Paginate results
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        $formattedData = $paginated->getCollection()->map(function ($video) use ($language) {
            $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
            $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

            // Set title and description based on language
            $video->title = $language === 'chinese' ?  ($video->title_chinese == null ? $video->title : $video->title_chinese) : $video->title;
            $video->description = $language === 'chinese' ?  ($video->description_chinese == null ? $video->description : $video->description_chinese) : $video->description;
            $video->category_name = $language === 'chinese'
                ? (Category::where('id', $video->category_id)->value('category_name_chinese') ?? Category::where('id', $video->category_id)->value('category_name'))
                : Category::where('id', $video->category_id)->value('category_name');

            unset($video->title_chinese, $video->description_chinese);

            return $video;
        });

        // Replace the collection with formatted data
        $paginated->setCollection($formattedData);

        return ApiResponse::success($paginated, 'Data fetched successfully!', 200);
    }

    public function videoContentforparent(Request $request)
    {
        // 1. Basic Inputs
        $language = $request->language ?? 'english';
        $search = $request->search ?? null;
        $filter = $request->filter ?? 'all';
        $perPage = $request->per_page ?? 10;
        $page = $request->page ?? 1;
        $category_id = $request->category_id;

        $user = auth()->user();
        $userId = $user->id;
        $userType = $user->user_type;

        $schoolId = null;
        if ($userType == 'parent') {
            $schoolId = $user->school_id;
        } elseif ($userType == 'child') {

            $parent = User::find($user->parent_id);
            $schoolId = $parent ? $parent->school_id : null;
        }

        // $query = VideoContent::with('category')
        //     ->where('status', 'active')
        //     ->whereIn('user_type', ['parent', 'both']);

        $query = VideoContent::with('category')
            ->where('status', 'active')
            ->whereIn('user_type', ['parent', 'both'])
            ->where(function($q) {
                $q->whereNull('mood')
                  ->orWhere('mood', '!=', 'mood');
            });

        $schoolId = null;
        if ($userType == 'parent') {
            $schoolId = $user->school_id;
        } elseif ($userType == 'child') {
            $parent = User::find($user->parent_id);
            $schoolId = $parent ? $parent->school_id : null;
        }

        // Query Filter
        $query->where(function ($q) use ($schoolId) {
            if (!empty($schoolId)) {
                $q->whereJsonContains('school_id', $schoolId)
                    ->orWhereJsonContains('school_id', (string)$schoolId)
                    ->orWhereNull('school_id')
                    ->orWhere('school_id', '[]')
                    ->orWhere('school_id', '');
            } else {
                $q->whereNull('school_id')
                    ->orWhere('school_id', '[]')
                    ->orWhere('school_id', '');
            }
        });

        if (!empty($category_id)) {
            $query->where('category_id', $category_id);
        }

        if ($search) {
            $search = strtolower(trim($search));
            if (strlen($search) < 3) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("LOWER(title) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(title_chinese) LIKE ?", ["%{$search}%"])
                        ->orWhere(function ($qq) use ($search) {
                            $qq->whereNotNull('featured_key')
                                ->whereRaw("LOWER(featured_key) LIKE ?", ["%{$search}%"]);
                        });
                });
            }
        }

        switch ($filter) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'favrits':
                $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'favourite')
                        ->where('ulv.user_id', $userId);
                })->select('video_contents.*');
                break;
            case 'tranding':
                $query->leftJoin('user_liked_videos as ulv_t', function ($j) {
                    $j->on('video_contents.id', '=', 'ulv_t.video_id')->where('ulv_t.type', 'like');
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv_t.id) as like_count'))
                    ->groupBy('video_contents.id')
                    ->orderByDesc('like_count');
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $formattedData = $paginated->getCollection()->map(function ($video) use ($language) {
            $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
            $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

            $isChinese = ($language === 'chinese');
            $video->title = $isChinese ? ($video->title_chinese ?? $video->title) : $video->title;
            $video->description = $isChinese ? ($video->description_chinese ?? $video->description) : $video->description;

            if ($video->category) {
                $video->category_name = $isChinese
                    ? ($video->category->category_name_chinese ?? $video->category->category_name)
                    : $video->category->category_name;
            }

            unset($video->title_chinese, $video->description_chinese, $video->category);
            return $video;
        });

        $paginated->setCollection($formattedData);

        return ApiResponse::success($paginated, 'Data fetched successfully!', 200);
    }

    public function videoContentforchild(Request $request)
    {
        $language    = $request->language ?? 'english';
        $search      = $request->search ?? null;
        $filter      = $request->filter ?? 'all';
        $perPage     = $request->per_page ?? 10;
        $page        = $request->page ?? 1;
        $categoryId  = $request->category_id;

        // 1. Context Resolution Layer
        $loggedInUser = auth()->user();
        $targetUser   = null;

        $isTeacher = ($loggedInUser && ($loggedInUser->user_role_id == 5 || $loggedInUser->user_type === 'teacher'));

        if ($request->filled('user_id')) {
            $candidate = User::find($request->user_id);

            // A body-supplied user_id used to override the token unconditionally.
            // Teachers keep their cross-child access; everyone else may only
            // target themselves or their own child.
            if ($isTeacher || $this->canActOnUser($candidate)) {
                $targetUser = $candidate;
            } else {
                return $this->unauthorisedTargetResponse($language);
            }
        } elseif ($loggedInUser && $loggedInUser->user_type === 'child') {
            $targetUser = $loggedInUser;
        }

        if (!$targetUser) {
            return ApiResponse::error(
                $language == 'english' ? "Target user profile not found." : "找不到目标用户个人资料。",
                200,
                null,
                (object) []
            );
        }

        // 2. Dynamic Senior-Level Role Bypass Check
        // ✅ Allow access if the target is a child OR if the logged-in user is a Teacher (Role 5)
        if ($targetUser->user_type !== 'child' && !$isTeacher) {
            return ApiResponse::error(
                $language == 'english' ? "Access denied. Target must be a child context." : "访问被拒绝。目标必须是儿童上下文。",
                200,
                null,
                (object) []
            );
        }

        // 3. Assign Parameters Safely
        $userId = $targetUser->id;

        // If a teacher is checking, they might not have a parent_id, so we fallback safely
        if ($targetUser->user_type === 'child') {
            $parent   = User::find($targetUser->parent_id);
            $schoolId = $parent ? $parent->school_id : $targetUser->school_id;
        } else {
            // If it's the teacher themselves acting as the target context framework
            $schoolId = $targetUser->school_id;
        }

        // 4. Dynamic Age Range Matrix Calculation Engine
        // If the teacher logs in without a specific child_id, default to show all age ranges safely
        $allowedAgeRanges = ['11-18', '11-14', '15-18'];

        if ($targetUser->user_type === 'child' && !empty($targetUser->dob)) {
            try {
                $dob = Carbon::parse($targetUser->dob . '-01');
                $age = $dob->age;

                $allowedAgeRanges = ['11-18']; // Reset to calculate specific brackets
                if ($age >= 11 && $age <= 14) {
                    $allowedAgeRanges[] = '11-14';
                } elseif ($age >= 15 && $age <= 18) {
                    $allowedAgeRanges[] = '15-18';
                }
            } catch (\Exception $e) {
                \Log::error('Child API DOB Parsing Fallback Exception: ' . $e->getMessage());
            }
        }

        // 4. Content Matrix Database Processing Logic
        $query = VideoContent::with('category')
            ->where('status', 'active')
            ->whereIn('user_type', ['child', 'both']) // Restricts content accurately to child targets
            ->whereIn('age', $allowedAgeRanges)
            ->where(function($q) {
                $q->whereNull('mood')
                  ->orWhere('mood', '!=', 'mood');
            });

        $query->where(function ($q) use ($schoolId) {
            if (!empty($schoolId)) {
                $q->whereJsonContains('school_id', $schoolId)
                    ->orWhereJsonContains('school_id', (string)$schoolId)
                    ->orWhereNull('school_id')
                    ->orWhere('school_id', '[]')
                    ->orWhere('school_id', '');
            } else {
                $q->whereNull('school_id')
                    ->orWhere('school_id', '[]')
                    ->orWhere('school_id', '');
            }
        });

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $search = strtolower(trim($search));
            if (strlen($search) < 3) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("LOWER(title) LIKE ?", ["%{$search}%"])
                        ->orWhereRaw("LOWER(title_chinese) LIKE ?", ["%{$search}%"])
                        ->orWhere(function ($qq) use ($search) {
                            $qq->whereNotNull('featured_key')
                                ->whereRaw("LOWER(featured_key) LIKE ?", ["%{$search}%"]);
                        });
                });
            }
        }

        switch ($filter) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'favrits':
                $query->join('user_liked_videos as ulv', function ($join) use ($userId) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'favourite')
                        ->where('ulv.user_id', $userId);
                })->select('video_contents.*');
                break;
            case 'tranding':
                $query->leftJoin('user_liked_videos as ulv_t', function ($j) {
                    $j->on('video_contents.id', '=', 'ulv_t.video_id')->where('ulv_t.type', 'like');
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv_t.id) as like_count'))
                    ->groupBy('video_contents.id')
                    ->orderByDesc('like_count');
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $formattedData = $paginated->getCollection()->map(function ($video) use ($language) {
            $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
            $video->thumbnail  = getImagePathUrl($video->thumbnail, 'assets/images');

            $isChinese = ($language === 'chinese');
            $video->title       = $isChinese ? ($video->title_chinese ?? $video->title) : $video->title;
            $video->description = $isChinese ? ($video->description_chinese ?? $video->description) : $video->description;

            if ($video->category) {
                $video->category_name = $isChinese
                    ? ($video->category->category_name_chinese ?? $video->category->category_name)
                    : $video->category->category_name;
            }

            unset($video->title_chinese, $video->description_chinese, $video->category);
            return $video;
        });

        $paginated->setCollection($formattedData);

        return ApiResponse::success($paginated, 'Data fetched successfully!', 200);
    }
}
