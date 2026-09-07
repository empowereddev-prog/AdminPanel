<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleSuggestion;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KnowledgeSession;
use App\Models\VideoContent;
use App\Models\Category;
use App\Models\ChildMood;
use App\Models\Mood;
use App\Models\UserLikedVideo;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KnowledgeSessionController extends Controller
{
    public function KnowledgeSession(Request $request)
    {
        // Determine the language (default: English)
        $language = $request->language ?? 'english';

        // Validation messages in both languages
        $messages = [
            'user_id.required' => $language === 'chinese' ? '儿童 ID 是必需的。' : 'User ID is required.',
            'user_id.exists'   => $language === 'chinese' ? '儿童 ID 无效。' : 'Invalid user ID.',
        ];

        // Validate request with custom messages
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ], $messages);

        // Get child data
        $child = User::findOrFail($request->user_id);

        // Calculate child's age
        $childAge = \Carbon\Carbon::parse($child->dob)->age;

        // Fetch all knowledge sessions
        $knowledgeSessions = KnowledgeSession::where('user_type', 'child')->where('status', 'active')->get();

        // Filter sessions where child's age falls within the range
        $filteredSessions = $knowledgeSessions->filter(function ($session) use ($childAge) {
            if (strpos($session->age, '-') !== false) {
                list($minAge, $maxAge) = explode('-', $session->age);
                return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
            }
            return false;
        });
        // $filteredSessions = $filteredSessions->map(function ($session) {
        //     $session->banner_image = asset('assets/images/' . $session->banner_image);
        //     return $session;
        // });

        // Modify the sessions to include the correct title and description based on language
        $filteredSessions = $filteredSessions->map(function ($session) use ($language) {
            $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');

            // Set title and description based on language
            $session->title = $language === 'chinese' ? ($session->title_chinese == null ? $session->title : $session->title_chinese) : $session->title;
            $session->description = $language === 'chinese' ? ($session->description_chinese == null ? $session->description : $session->description_chinese) : $session->description;

            // Remove the unnecessary fields
            unset($session->title_chinese, $session->description_chinese);

            return $session;
        });

        return response()->json([
            'status'  => true,
            'message' => $language === 'chinese' ? '找到匹配的知识课程！' : 'Matching Knowledge Sessions Found!',
            'data'    => $filteredSessions->values(),
        ], 200);
    }

    // public function KnowledgeSessionDetails(Request $request)
    // {
    //     $session_id = $request->id;
    //     $language = $request->language;
    //     $user_id = auth()->user()->id;
    //     $session_details =  KnowledgeSession::where('id', $session_id)->first();
    //     if ($session_details) {
    //         $session_details->banner_image = getImagePathUrl($session_details->banner_image, 'assets/images');
    //         // Set the correct title based on language
    //         $session_details->title = $language === 'chinese' ?  ($session_details->title_chinese == null ? $session_details->title : $session_details->title_chinese) : $session_details->title;
    //         $session_details->description = $language === 'chinese' ?  ($session_details->description_chinese == null ? $session_details->description : $session_details->description_chinese) : $session_details->description;

    //         // Remove the unnecessary title_chinese key

    //         $likeStatus = DB::table('user_articale_likes')
    //             ->where('article_id', $request->id)
    //             ->where('user_id', $user_id)
    //             ->pluck('type')
    //             ->toArray();

    //         $session_details->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
    //         $session_details->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
    //         $session_details->is_favourite = in_array('favourite', $likeStatus) ? 'yes' : 'no';





    //         unset($session_details->title_chinese);
    //         unset($session_details->description_chinese);
    //         $session_details->category_name = $session_details->category->category_name;
    //         // $session_details->category_name = $session_details->category->category_name;

    //         // Fetch related articles from article_suggestions
    //         $articleSuggestions = ArticleSuggestion::where('knowledge_session_id', $session_id)->get();
    //         $articlesData = collect();

    //         foreach ($articleSuggestions as $suggestion) {
    //             $articleIds = explode(',', $suggestion->article_ids); // may have multiple IDs
    //             $articles = KnowledgeSession::whereIn('id', $articleIds)->get();

    //             $articlesData = $articlesData->merge($articles->map(function ($article) use ($language) {
    //                 return [
    //                     'article_id' => $article->id,
    //                     'title'      => $language === 'chinese' ? ($article->title_chinese ?? $article->title) : $article->title,
    //                     'banner_image'  =>  $article->banner_image ? getImagePathUrl($article->banner_image, 'assets/images') : null,
    //                 ];
    //             }));
    //         }
    //         // Remove duplicate articles if any
    //         $session_details->articles = $articlesData->unique('article_id')->values();

    //         return response()->json([
    //             'status' => true,
    //             'message' => $language == 'chinese' ? '会话详细信息获取成功！' : 'Session details fetched successfully!',
    //             'data' => $session_details
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'chinese' ? '未找到会话详细信息。' : 'Session details not found.',
    //             'data' => null
    //         ], 201);
    //     }
    // }

    public function KnowledgeSessionDetails(Request $request)
    {
        $session_id = $request->id;
        $language = $request->language;
        $user_id = auth()->user()->id;
        $device = $request->device ?? 'mobile';
        $fontSize = ($device === 'tablet') ? '24px' : '16px';

        $deepLink = app(\App\Services\DeepLinkService::class)->resolve('article', $session_id, auth()->user(), false);
        if ($deepLink['status'] !== \App\Services\DeepLinkService::STATUS_OK) {
            $messages = [
                'not_found' => $language == 'chinese' ? '未找到会话详细信息。' : 'Content unavailable.',
                'unpublished' => $language == 'chinese' ? '内容不可用。' : 'Content unavailable.',
                'forbidden_role' => $language == 'chinese' ? '此内容不适用于您的帐户。' : 'This content is not available for your account.',
                'subscription_required' => $language == 'chinese' ? '需要有效订阅。' : 'An active subscription is required.',
            ];
            return response()->json([
                'status' => false,
                'deeplink_status' => $deepLink['status'],
                'message' => $messages[$deepLink['status']] ?? 'Content unavailable.',
                'canonical_url' => $deepLink['canonical_url'],
                'data' => null
            ], $deepLink['http_status']);
        }

        $session_details =  KnowledgeSession::where('id', $session_id)->first();

        if ($session_details) {
            $session_details->banner_image = getImagePathUrl($session_details->banner_image, 'assets/images');
            $session_details->title = $language === 'chinese' ?  ($session_details->title_chinese == null ? $session_details->title : $session_details->title_chinese) : $session_details->title;
            $descriptionContent = $language === 'chinese' ?  ($session_details->description_chinese == null ? $session_details->description : $session_details->description_chinese) : $session_details->description;
            $descriptionContent = preg_replace('/font-size:[^;]*;?/i', '', $descriptionContent);
            $session_details->description = '<div style="font-size: ' . $fontSize . ';">' . $descriptionContent . '</div>';
            $likeStatus = DB::table('user_articale_likes')
                ->where('article_id', $request->id)
                ->where('user_id', $user_id)
                ->pluck('type')
                ->toArray();

            $session_details->is_like = in_array('like', $likeStatus) ? 'yes' : 'no';
            $session_details->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
            $session_details->is_favourite = in_array('favourite', $likeStatus) ? 'yes' : 'no';


            unset($session_details->title_chinese);
            unset($session_details->description_chinese);
            $session_details->category_name = $session_details->category->category_name;


            $articleSuggestions = ArticleSuggestion::where('knowledge_session_id', $session_id)->get();
            $articlesData = collect();

            foreach ($articleSuggestions as $suggestion) {
                $articleIds = explode(',', $suggestion->article_ids); // may have multiple IDs
                $articles = KnowledgeSession::whereIn('id', $articleIds)->get();

                $articlesData = $articlesData->merge($articles->map(function ($article) use ($language) {
                    return [
                        'article_id' => $article->id,
                        'title'      => $language === 'chinese' ? ($article->title_chinese ?? $article->title) : $article->title,
                        'banner_image'  =>  $article->banner_image ? getImagePathUrl($article->banner_image, 'assets/images') : null,
                    ];
                }));
            }

            $session_details->articles = $articlesData->unique('article_id')->values();
            $session_details->canonical_url = $deepLink['canonical_url'];

            return response()->json([
                'status' => true,
                'message' => $language == 'chinese' ? '会话详细信息获取成功！' : 'Session details fetched successfully!',
                'data' => $session_details
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'deeplink_status' => 'not_found',
                'message' => $language == 'chinese' ? '未找到会话详细信息。' : 'Session details not found.',
                'data' => null
            ], 404);
        }
    }


    public function featuredSession1(Request $request)
    {
        $language = $request->language ?? 'english';

        // Validation messages in both languages
        $messages = [
            'user_id.required' => $language === 'chinese' ? '儿童 ID 是必需的。' : 'User ID is required.',
            'user_id.exists'   => $language === 'chinese' ? '儿童 ID 无效。' : 'Invalid user ID.',
        ];

        // Validate request with custom messages
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ], $messages);

        // Get child data
        $child = User::findOrFail($request->user_id);

        // Calculate child's age
        $childAge = \Carbon\Carbon::parse($child->dob)->age;

        // Fetch and filter featured knowledge sessions
        $knowledgeSessions = KnowledgeSession::where('status', 'active')->whereIn('user_type', ['child', 'both'])
            ->get()
            ->filter(function ($session) use ($childAge) {
                if (strpos($session->age, '-') !== false) {
                    list($minAge, $maxAge) = explode('-', $session->age);
                    return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
                }
                return false;
            })
            ->map(function ($session) use ($language) {
                $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
                $session->type = 'knowledge_session';

                // Set the correct title based on language
                $session->title = $language === 'chinese' ?  ($session->title_chinese == null ? $session->title : $session->title_chinese) : $session->title;
                $session->description = $language === 'chinese' ?  ($session->description_chinese == null ? $session->description : $session->description_chinese) : $session->description;

                // Remove the unnecessary title_chinese key
                unset($session->title_chinese);
                unset($session->description_chinese);


                return $session;
            });

        // $videoContents = VideoContent::where('is_featured', 'yes')
        //     ->where('status', 'active')->whereIn('user_type', ['child', 'both'])
        //     ->latest()->get()
        $videoContents = VideoContent::where('is_featured', 'yes')
            ->where('status', 'active')
            ->whereIn('user_type', ['child', 'both'])
            ->where(function($q) {
                $q->whereNull('mood')
                  ->orWhere('mood', '!=', 'mood');
            })
            ->latest()->get()
            ->filter(function ($video) use ($childAge) {
                if (strpos($video->age, '-') !== false) {
                    list($minAge, $maxAge) = explode('-', $video->age);
                    return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
                }
                return false;
            })
            ->map(function ($video) use ($language) {
                $video->type = 'video_content';

                // Set the correct title based on language
                $video->title = $language === 'chinese' ?  ($video->title_chinese == null ? $video->title : $video->title_chinese) : $video->title;
                $video->description = $language === 'chinese' ?  ($video->description_chinese == null ? $video->description : $video->description_chinese) : $video->description;
                $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
                $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');
                // Remove the unnecessary title_chinese key
                unset($video->title_chinese);
                unset($video->description_chinese);

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

        $mergedData = $knowledgeSessions->merge($videoContents)->values();
        $childId =  auth()->user()->id;

        $topEmotions = $this->getTopEmotions($childId, 3, 'english');
        return response()->json([
            'status' => true,
            'message' => 'Featured data fetched successfully!',
            'data' => $mergedData,
            'top_mood_emotion' => $topEmotions
        ], 200);
    }
    public function featuredSession(Request $request)
{
    $language = $request->language ?? 'english';

    $messages = [
        'user_id.required' => $language === 'chinese' ? '儿童 ID 是必需的。' : 'User ID is required.',
        'user_id.exists'   => $language === 'chinese' ? '儿童 ID 无效。' : 'Invalid user ID.',
    ];

    $request->validate([
        'user_id' => 'required|exists:users,id',
    ], $messages);

    // =======================
    // CHILD + AGE
    // =======================
    $child = User::findOrFail($request->user_id);
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
    // KNOWLEDGE SESSION
    // =======================
    $knowledgeSessions = KnowledgeSession::where('status', 'active')
        ->whereIn('user_type', ['child', 'both'])
        ->get()
        ->filter(function ($session) use ($childAge) {

            if (!$session->age) return true;

            if (strpos($session->age, '-') !== false) {
                [$minAge, $maxAge] = explode('-', $session->age);
                return $childAge >= (int)$minAge && $childAge <= (int)$maxAge;
            }

            return false;
        })
        ->map(function ($session) use ($language) {

            $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
            $session->type = 'knowledge_session';

            $session->title = $language === 'chinese'
                ? ($session->title_chinese ?? $session->title)
                : $session->title;

            $session->description = $language === 'chinese'
                ? ($session->description_chinese ?? $session->description)
                : $session->description;

            unset($session->title_chinese, $session->description_chinese);

            return $session;
        });

    // =======================
    // VIDEO CONTENT (FIXED)
    // =======================
    $videoContents = VideoContent::with('category')
        ->useWritePdo() //  IMPORTANT FIX
        ->where('is_featured', 'yes')
        ->where('status', 'active')
        ->whereIn('user_type', ['child', 'both'])

        // CRITICAL FIX: Exclude wellness mood videos from featured section
        ->where(function($q) {
            $q->whereNull('mood')
              ->orWhere('mood', '!=', 'mood');
        })

        //  SCHOOL FILTER (FINAL FIX)
        ->where(function ($q) use ($parentSchoolIds) {

            // Handle NULL + empty JSON
            $q->where(function ($sub) {
                $sub->whereNull('school_id')
                    ->orWhereRaw("JSON_LENGTH(COALESCE(school_id, '[]')) = 0");
            });

            // School specific
            if (!empty($parentSchoolIds)) {
                $q->orWhere(function ($inner) use ($parentSchoolIds) {
                    foreach ($parentSchoolIds as $id) {
                        $inner->orWhereJsonContains('school_id', $id);
                    }
                });
            }

        })

        ->latest()
        ->get()

        ->filter(function ($video) use ($childAge) {

            if (!$video->age) return true;

            if (strpos($video->age, '-') !== false) {
                [$minAge, $maxAge] = explode('-', $video->age);
                return $childAge >= (int)$minAge && $childAge <= (int)$maxAge;
            }

            return false;
        })

        ->map(function ($video) use ($language) {

            $video->type = 'video_content';

            $video->title = $language === 'chinese'
                ? ($video->title_chinese ?? $video->title)
                : $video->title;

            $video->description = $language === 'chinese'
                ? ($video->description_chinese ?? $video->description)
                : $video->description;

            $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
            $video->thumbnail  = getImagePathUrl($video->thumbnail, 'assets/images');

            unset($video->title_chinese, $video->description_chinese);

            $userId = auth()->id();

            $likeStatus = UserLikedVideo::where('user_id', $userId)
                ->where('video_id', $video->id)
                ->pluck('type')
                ->toArray();

            $video->is_like    = in_array('like', $likeStatus) ? 'yes' : 'no';
            $video->is_dislike = in_array('dislike', $likeStatus) ? 'yes' : 'no';
            $video->is_heart   = in_array('favourite', $likeStatus) ? 'yes' : 'no';

            return $video;
        });

    // =======================
    // MERGE DATA
    // =======================
    $mergedData = $knowledgeSessions->merge($videoContents)->values();

    $childId = auth()->user()->id;
    $topEmotions = $this->getTopEmotions($childId, 3, 'english');

    return response()->json([
        'status' => true,
        'message' => 'Featured data fetched successfully!',
        'data' => $mergedData,
        'top_mood_emotion' => $topEmotions
    ], 200);
}


    public function getTopEmotions(int $childId, int $limit = 3, string $language = 'english'): Collection
    {

        // $childId =  auth()->user()->id;
        // $topEmotions = $this->getTopEmotions($childId, 3, $language);
        // Use current month and year
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $entries = ChildMood::where('child_id', $childId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->get();

        $moodFrequency = [];

        foreach ($entries as $entry) {
            $mood = Mood::find($entry->mood_id);
            if (!$mood) continue;

            $moodName = $language === 'chinese' ? ($mood->name_chinese ?? $mood->name) : $mood->name;

            if (isset($moodFrequency[$moodName])) {
                $moodFrequency[$moodName]['count'] += 1;
            } else {
                $moodFrequency[$moodName] = [
                    'count' => 1,
                    'image' => $mood->image,
                    'color' => $mood->color ?? '#CCCCCC',
                ];
            }
        }

        return collect($moodFrequency)
            ->sortByDesc('count')
            ->take($limit)
            ->map(function ($item, $moodName) {
                return [
                    'mood_name' => $moodName,
                    'count' => $item['count'],
                    'image' => $item['image'],
                    'color' => $item['color'],
                ];
            })->values();
    }



    public function KnowledgeSessionforParent(Request $request)
    {
        // Determine the language (default: English)
        $language = $request->language ?? 'english';

        // Validation messages in both languages
        $messages = [
            'user_id.required' => $language === 'chinese' ? '儿童 ID 是必需的。' : 'User ID is required.',
            'user_id.exists'   => $language === 'chinese' ? '儿童 ID 无效。' : 'Invalid user ID.',
        ];

        // Validate request with custom messages
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ], $messages);

        // Get child data
        $child = User::findOrFail($request->user_id);

        // Calculate child's age
        //  $childAge = \Carbon\Carbon::parse($child->dob)->age;

        // Fetch all knowledge sessions
        $knowledgeSessions = KnowledgeSession::where('user_type', 'parent')->where('status', 'active')->get();

        // Filter sessions where child's age falls within the range
        //  $filteredSessions = $knowledgeSessions->filter(function ($session) use ($childAge) {
        //     //  if (strpos($session->age, '-') !== false) {
        //     //      list($minAge, $maxAge) = explode('-', $session->age);
        //     //      return $childAge >= (int) $minAge && $childAge <= (int) $maxAge;
        //     //  }
        //      return false;
        //  });
        // $filteredSessions = $filteredSessions->map(function ($session) {
        //     $session->banner_image = asset('assets/images/' . $session->banner_image);
        //     return $session;
        // });

        // Modify the sessions to include the correct title and description based on language
        $filteredSessions = $knowledgeSessions->map(function ($session) use ($language) {
            $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');

            // Set title and description based on language
            $session->title = $language === 'chinese' ?  ($session->title_chinese == null ? $session->title : $session->title_chinese) : $session->title;
            $session->description = $language === 'chinese' ?  ($session->description_chinese == null ? $session->description : $session->description_chinese) : $session->description;

            // Remove the unnecessary fields
            unset($session->title_chinese, $session->description_chinese);

            return $session;
        });

        return response()->json([
            'status'  => true,
            'message' => $language === 'chinese' ? '找到匹配的知识课程！' : 'Matching Knowledge Sessions Found!',
            'data'    => $filteredSessions->values(),
        ], 200);
    }
    // public function featuredSessionforParent(Request $request)
    // {
    //     $language = $request->language ?? 'english';

    //     // Validation messages in both languages
    //     $messages = [
    //         'user_id.required' => $language === 'chinese' ? '儿童 ID 是必需的。' : 'User ID is required.',
    //         'user_id.exists'   => $language === 'chinese' ? '儿童 ID 无效。' : 'Invalid user ID.',
    //     ];

    //     // Validate request with custom messages
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //     ], $messages);

    //     // Get child data
    //     $child = User::findOrFail($request->user_id);

    //     // Calculate child's age

    //     // Fetch and filter featured knowledge sessions
    //     $knowledgeSessions = KnowledgeSession::where('user_type', 'parent')
    //         ->where('status', 'active')
    //         ->get()
    //         ->map(function ($session) use ($language) {
    //             $session->banner_image = asset('assets/images/' . $session->banner_image);
    //             $session->type = 'knowledge_session';

    //             // Set the correct title based on language
    //             $session->title = $language === 'chinese' ?  ($session->title_chinese == null ? $session->title : $session->title_chinese) : $session->title;
    //             $session->description = $language === 'chinese' ?  ($session->description_chinese == null ? $session->description : $session->description_chinese) : $session->description;
    //             $session->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $session->category_id)->value('category_name_chinese') ?? Category::where('id', $session->category_id)->value('category_name'))
    //                 : Category::where('id', $session->category_id)->value('category_name');
    //             // Remove the unnecessary title_chinese key
    //             unset($session->title_chinese);
    //             unset($session->description_chinese);


    //             return $session;
    //         });

    //     $videoContents = VideoContent::where('is_featured', 'yes')
    //         ->where('user_type', 'parent')
    //         ->where('status', 'active')
    //         ->get()
    //         ->map(function ($video) use ($language) {
    //             $video->type = 'video_content';

    //             // Set the correct title based on language
    //             $video->title = $language === 'chinese' ?  ($video->title_chinese == null ? $video->title : $video->title_chinese) : $video->title;
    //             $video->description = $language === 'chinese' ?  ($video->description_chinese == null ? $video->description : $video->description_chinese) : $video->description;
    //             $video->video_link = asset('assets/video/' . $video->video_link);
    //             $video->thumbnail = asset('assets/images/' . $video->thumbnail);
    //             $video->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $video->category_id)->value('category_name_chinese') ?? Category::where('id', $video->category_id)->value('category_name'))
    //                 : Category::where('id', $video->category_id)->value('category_name');
    //             // Remove the unnecessary title_chinese key
    //             unset($video->title_chinese);
    //             unset($video->description_chinese);

    //             return $video;
    //         });

    //     $mergedData = $knowledgeSessions->merge($videoContents)->values();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Featured data fetched successfully!',
    //         'data' => $mergedData,
    //     ], 200);
    // }
    // public function featuredSessionforParent(Request $request)
    // {
    //     $language = $request->language ?? 'english';

    //     $user_id = auth()->user()->id;
    //     // Get child data
    //     $child = User::findOrFail($user_id);

    //     // Knowledge Sessions
    //     $knowledgeSessions = KnowledgeSession::where('user_type', 'parent')
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                       ->orWhere('description_chinese', 'like', '%' . $request->search . '%');
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                       ->orWhere('description', 'like', '%' . $request->search . '%');
    //                 }
    //             });
    //         })
    //         ->get()
    //         ->map(function ($session) use ($language) {
    //             $session->banner_image = asset('assets/images/' . $session->banner_image);
    //             $session->type = 'knowledge_session';

    //             $session->title = $language === 'chinese'
    //                 ? ($session->title_chinese ?? $session->title)
    //                 : $session->title;

    //             $session->description = $language === 'chinese'
    //                 ? ($session->description_chinese ?? $session->description)
    //                 : $session->description;

    //             $session->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $session->category_id)->value('category_name_chinese') ??
    //                    Category::where('id', $session->category_id)->value('category_name'))
    //                 : Category::where('id', $session->category_id)->value('category_name');

    //             unset($session->title_chinese, $session->description_chinese);

    //             return $session;
    //         });

    //     // Video Contents (now with same filter!)
    //     $videoContents = VideoContent::where('is_featured', 'yes')
    //         // ->where('user_type', 'parent')
    //         ->whereIn('user_type', ['parent', 'both'])
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                       ->orWhere('description_chinese', 'like', '%' . $request->search . '%');
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                       ->orWhere('description', 'like', '%' . $request->search . '%');
    //                 }
    //             });
    //         })
    //         ->get()
    //         ->map(function ($video) use ($language) {
    //             $video->type = 'video_content';

    //             $video->title = $language === 'chinese'
    //                 ? ($video->title_chinese ?? $video->title)
    //                 : $video->title;

    //             $video->description = $language === 'chinese'
    //                 ? ($video->description_chinese ?? $video->description)
    //                 : $video->description;

    //             $video->video_link = asset('assets/video/' . $video->video_link);
    //             $video->thumbnail = asset('assets/images/' . $video->thumbnail);

    //             $video->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $video->category_id)->value('category_name_chinese') ??
    //                    Category::where('id', $video->category_id)->value('category_name'))
    //                 : Category::where('id', $video->category_id)->value('category_name');

    //             unset($video->title_chinese, $video->description_chinese);

    //             return $video;
    //         });

    //     $mergedData = $knowledgeSessions->merge($videoContents)->values();
    //     $videoCategory = Category::where('status','active')->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->get();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Featured data fetched successfully!',
    //         'category' => $videoCategory,
    //         'data' => $mergedData,
    //     ], 200);
    // }


    // public function featuredSessionforParent(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $filter = $request->filter ?? 'all';
    //     $user_id = auth()->user()->id;

    //     // Get child data
    //     $child = User::findOrFail($user_id);

    //     // Knowledge Sessions
    //     $knowledgeQuery = KnowledgeSession::where('user_type', 'parent')
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%");
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%");
    //                 }
    //             });
    //         });

    //     // Apply filter logic for knowledge sessions
    //     switch ($filter) {
    //         case 'newest':
    //             $knowledgeQuery->orderBy('created_at', 'desc');
    //             break;

    //         case 'favrits':
    //             $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
    //                 $join->on('knowledge_sessions.id', '=', 'ual.article_id')
    //                     ->where('ual.type', 'favourite')
    //                     ->where('ual.user_id', $user_id);
    //             })
    //                 ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as favourite_count'))
    //                 ->groupBy('knowledge_sessions.id')
    //                 ->having('favourite_count', '>', 0)
    //                 ->orderByDesc('favourite_count');
    //             break;

    //         case 'tranding':
    //             $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
    //                 $join->on('knowledge_sessions.id', '=', 'ual.article_id')
    //                     ->where('ual.type', 'like')
    //                     ->where('ual.user_id', $user_id);
    //             })
    //                 ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as like_count'))
    //                 ->groupBy('knowledge_sessions.id')
    //                 ->having('like_count', '>', 0)
    //                 ->orderByDesc('like_count');
    //             break;

    //         case 'all':
    //         default:
    //             // No extra filters
    //             break;
    //     }

    //     $knowledgeSessions = $knowledgeQuery->get()
    //         ->map(function ($session) use ($language) {
    //             $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
    //             $session->type = 'knowledge_session';

    //             $session->title = $language === 'chinese'
    //                 ? ($session->title_chinese ?? $session->title)
    //                 : $session->title;

    //             $session->description = $language === 'chinese'
    //                 ? ($session->description_chinese ?? $session->description)
    //                 : $session->description;

    //             $session->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $session->category_id)->value('category_name_chinese') ??
    //                     Category::where('id', $session->category_id)->value('category_name'))
    //                 : Category::where('id', $session->category_id)->value('category_name');

    //             unset($session->title_chinese, $session->description_chinese);
    //             return $session;
    //         });

    //     // Video Contents (same as before)
    //     $videoQuery = VideoContent::where('is_featured', 'yes')
    //         ->whereIn('user_type', ['parent', 'both'])
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%")
    //                         ->orWhere('description_chinese', 'like', '%' . $request->search . '%');
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%")
    //                         ->orWhere('description', 'like', '%' . $request->search . '%');
    //                 }
    //             });
    //         });

    //     // Apply same filter logic for video contents
    //     switch ($filter) {
    //         case 'newest':
    //             $videoQuery->orderBy('created_at', 'desc');
    //             break;

    //         case 'favrits':
    //             $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
    //                 $join->on('video_contents.id', '=', 'ulv.video_id')
    //                     ->where('ulv.type', 'favourite')
    //                     ->where('ulv.user_id', $user_id);
    //             })
    //                 ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
    //                 ->groupBy('video_contents.id')
    //                 ->having('favourite_count', '>', 0)
    //                 ->orderByDesc('favourite_count');
    //             break;

    //         case 'tranding':
    //             $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
    //                 $join->on('video_contents.id', '=', 'ulv.video_id')
    //                     ->where('ulv.type', 'like')
    //                     ->where('ulv.user_id', $user_id);
    //             })
    //                 ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
    //                 ->groupBy('video_contents.id')
    //                 ->having('like_count', '>', 0)
    //                 ->orderByDesc('like_count');
    //             break;

    //         case 'all':
    //         default:
    //             // No extra filters
    //             break;
    //     }

    //     $videoContents = $videoQuery->get()
    //         ->map(function ($video) use ($language) {
    //             $video->type = 'video_content';
    //             $video->title = $language === 'chinese'
    //                 ? ($video->title_chinese ?? $video->title)
    //                 : $video->title;

    //             $video->description = $language === 'chinese'
    //                 ? ($video->description_chinese ?? $video->description)
    //                 : $video->description;

    //             $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
    //             $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

    //             $video->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $video->category_id)->value('category_name_chinese') ??
    //                     Category::where('id', $video->category_id)->value('category_name'))
    //                 : Category::where('id', $video->category_id)->value('category_name');

    //             unset($video->title_chinese, $video->description_chinese);
    //             return $video;
    //         });

    //     $mergedData = $knowledgeSessions->merge($videoContents)->values();
    //     $videoCategory = Category::where('status', 'active')
    //         ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
    //         ->get();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Featured data fetched successfully!',
    //         'category' => $videoCategory,
    //         'data' => $mergedData,
    //     ], 200);
    // }
    //  modify for 12/12/25
    // public function featuredSessionforParent(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $filter = $request->filter ?? 'all';
    //     $user_id = auth()->user()->id;

    //     // Get child data
    //     $child = User::findOrFail($user_id);

    //     // Knowledge Sessions
    //     $knowledgeQuery = KnowledgeSession::where('user_type', 'parent')
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%");
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%");
    //                 }
    //             });
    //         });

    //     $skipKnowledgeQueryExecution = false;
    //     $knowledgeSessions = collect();
    //     // Apply filter logic for knowledge sessions
    //     switch ($filter) {
    //         case 'newest':
    //             $knowledgeQuery->orderBy('created_at', 'desc');
    //             break;

    //         case 'favrits':
    //             $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
    //                 $join->on('knowledge_sessions.id', '=', 'ual.article_id')
    //                     ->where('ual.type', 'favourite')
    //                     ->where('ual.user_id', $user_id);
    //             })
    //                 ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as favourite_count'))
    //                 ->groupBy('knowledge_sessions.id')
    //                 ->having('favourite_count', '>', 0)
    //                 ->orderByDesc('favourite_count');
    //             break;

    //         case 'tranding':
    //             $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
    //                 $join->on('knowledge_sessions.id', '=', 'ual.article_id')
    //                     ->where('ual.type', 'like')
    //                     ->where('ual.user_id', $user_id);
    //             })
    //                 ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as like_count'))
    //                 ->groupBy('knowledge_sessions.id')
    //                 ->having('like_count', '>', 0)
    //                 ->orderByDesc('like_count');
    //             break;

    //         case 'all':
    //         default:

    //             // Fetch all sessions (filtered or not)
    //             $allSessions = KnowledgeSession::where('user_type', 'parent')
    //                 ->where('status', 'active')
    //                 ->when($request->category_id, function ($query) use ($request) {
    //                     return $query->where('category_id', $request->category_id);
    //                 })
    //                 ->orderBy('created_at', 'desc')
    //                 ->get();

    //             // ⭐ Apply limit = ONLY IF category_id is NOT provided
    //             if (!$request->category_id) {
    //                 $allSessions = $allSessions
    //                     ->groupBy('category_id')
    //                     ->map(function ($group) {
    //                         return $group->take(3);  // latest 3 per category
    //                     })
    //                     ->flatten(1);
    //             } else {
    //                 // ⭐ If category_id is present → return all records, but keep grouped format
    //                 $allSessions = $allSessions->groupBy('category_id')->flatten(1);
    //             }

    //             // Map category-wise groups
    //             $knowledgeSessions = $allSessions->map(function ($session) use ($language) {

    //                 $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
    //                 $session->type = 'knowledge_session';

    //                 $session->title = $language === 'chinese'
    //                     ? ($session->title_chinese ?? $session->title)
    //                     : $session->title;

    //                 $session->description = $language === 'chinese'
    //                     ? ($session->description_chinese ?? $session->description)
    //                     : $session->description;

    //                 $session->category_name = $language === 'chinese'
    //                     ? (Category::where('id', $session->category_id)->value('category_name_chinese') ??
    //                         Category::where('id', $session->category_id)->value('category_name'))
    //                     : Category::where('id', $session->category_id)->value('category_name');

    //                 unset($session->title_chinese, $session->description_chinese);

    //                 return $session;
    //             });

    //             $skipKnowledgeQueryExecution = true;
    //             break;
    //     }

    //     // Video Contents (unchanged)
    //     $videoQuery = VideoContent::where('is_featured', 'yes')
    //         ->whereIn('user_type', ['parent', 'both'])
    //         ->where('status', 'active')
    //         ->when($request->category_id, function ($query) use ($request) {
    //             return $query->where('category_id', $request->category_id);
    //         })
    //         ->when($request->search, function ($query) use ($request, $language) {
    //             return $query->where(function ($q) use ($request, $language) {
    //                 if ($language === 'chinese') {
    //                     $q->where('title_chinese', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%")
    //                         ->orWhere('description_chinese', 'like', '%' . $request->search . '%');
    //                 } else {
    //                     $q->where('title', 'like', '%' . $request->search . '%')
    //                         ->orWhere('featured_key', 'like', "%$request->search%")
    //                         ->orWhere('description', 'like', '%' . $request->search . '%');
    //                 }
    //             });
    //         });

    //     switch ($filter) {
    //         case 'newest':
    //             $videoQuery->orderBy('created_at', 'desc');
    //             break;

    //         case 'favrits':
    //             $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
    //                 $join->on('video_contents.id', '=', 'ulv.video_id')
    //                     ->where('ulv.type', 'favourite')
    //                     ->where('ulv.user_id', $user_id);
    //             })
    //                 ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
    //                 ->groupBy('video_contents.id')
    //                 ->having('favourite_count', '>', 0)
    //                 ->orderByDesc('favourite_count');
    //             break;

    //         case 'tranding':
    //             $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
    //                 $join->on('video_contents.id', '=', 'ulv.video_id')
    //                     ->where('ulv.type', 'like')
    //                     ->where('ulv.user_id', $user_id);
    //             })
    //                 ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
    //                 ->groupBy('video_contents.id')
    //                 ->having('like_count', '>', 0)
    //                 ->orderByDesc('like_count');
    //             break;

    //         case 'all':
    //         default:
    //             break;
    //     }

    //     $videoContents = $videoQuery->get()
    //         ->map(function ($video) use ($language) {
    //             $video->type = 'video_content';
    //             $video->title = $language === 'chinese'
    //                 ? ($video->title_chinese ?? $video->title)
    //                 : $video->title;

    //             $video->description = $language === 'chinese'
    //                 ? ($video->description_chinese ?? $video->description)
    //                 : $video->description;

    //             $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
    //             $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

    //             $video->category_name = $language === 'chinese'
    //                 ? (Category::where('id', $video->category_id)->value('category_name_chinese') ??
    //                     Category::where('id', $video->category_id)->value('category_name'))
    //                 : Category::where('id', $video->category_id)->value('category_name');

    //             unset($video->title_chinese, $video->description_chinese);
    //             return $video;
    //         });

    //     $mergedData = $knowledgeSessions->merge($videoContents)->values();

    //     $videoCategory = Category::where('status', 'active')
    //         ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
    //         ->get();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Featured data fetched successfully!',
    //         'category' => $videoCategory,
    //         'data' => $mergedData,
    //     ], 200);
    // }

    public function featuredSessionforParent(Request $request)
    {
        $language = $request->language ?? 'english';
        $filter = $request->filter ?? 'all';
        $user_id = auth()->user()->id;

        // Knowledge Sessions Query
        $knowledgeQuery = KnowledgeSession::where('user_type', 'parent')
            ->where('status', 'active')
            ->when($request->category_id, function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })
            ->when($request->search, function ($query) use ($request, $language) {
                return $query->where(function ($q) use ($request, $language) {
                    if ($language === 'chinese') {
                        $q->where('title_chinese', 'like', '%' . $request->search . '%')
                            // ->orWhere('featured_key', 'like', "%$request->search%");
                            ->orWhereRaw(
                                "LOWER(TRIM(featured_key)) LIKE ?",
                                ['%' . strtolower(trim($request->search)) . '%']
                            );
                    } else {
                        $q->where('title', 'like', '%' . $request->search . '%')
                            // ->orWhere('featured_key', 'like', "%$request->search%");
                            ->orWhereRaw(
                                "LOWER(TRIM(featured_key)) LIKE ?",
                                ['%' . strtolower(trim($request->search)) . '%']
                            );
                    }
                });
            });

        $skipKnowledgeQueryExecution = false;
        $knowledgeSessions = collect();

        // Knowledge Filters
        switch ($filter) {

            case 'newest':
                $knowledgeQuery->orderBy('created_at', 'desc');
                break;

            case 'favrits':
                $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
                    $join->on('knowledge_sessions.id', '=', 'ual.article_id')
                        ->where('ual.type', 'favourite')
                        ->where('ual.user_id', $user_id);
                })
                    ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as favourite_count'))
                    ->groupBy('knowledge_sessions.id')
                    ->having('favourite_count', '>', 0)
                    ->orderByDesc('favourite_count');
                break;

            case 'tranding':
                $knowledgeQuery->join('user_articale_likes as ual', function ($join) use ($user_id) {
                    $join->on('knowledge_sessions.id', '=', 'ual.article_id')
                        ->where('ual.type', 'like')
                        ->where('ual.user_id', $user_id);
                })
                    ->select('knowledge_sessions.*', DB::raw('COUNT(ual.id) as like_count'))
                    ->groupBy('knowledge_sessions.id')
                    ->having('like_count', '>', 0)
                    ->orderByDesc('like_count');
                break;

            case 'all':
            default:

                if ($request->search) {
                    break;
                }
                $allSessions = KnowledgeSession::where('user_type', 'parent')
                    ->where('status', 'active')
                    ->when($request->category_id, function ($query) use ($request) {
                        return $query->where('category_id', $request->category_id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

                if (!$request->category_id) {
                    $allSessions = $allSessions
                        ->groupBy('category_id')
                        ->map(function ($group) {
                            return $group->take(3);
                        })
                        ->flatten(1);
                } else {
                    $allSessions = $allSessions->groupBy('category_id')->flatten(1);
                }

                $knowledgeSessions = $allSessions->map(function ($session) use ($language) {

                    $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
                    $session->type = 'knowledge_session';

                    $session->title = $language === 'chinese'
                        ? ($session->title_chinese ?? $session->title)
                        : $session->title;

                    $session->description = $language === 'chinese'
                        ? ($session->description_chinese ?? $session->description)
                        : $session->description;

                    $session->category_name = $language === 'chinese'
                        ? (Category::where('id', $session->category_id)->value('category_name_chinese') ??
                            Category::where('id', $session->category_id)->value('category_name'))
                        : Category::where('id', $session->category_id)->value('category_name');

                    unset($session->title_chinese, $session->description_chinese);

                    return $session;
                });

                $skipKnowledgeQueryExecution = true;
                break;
        }

        if (!$skipKnowledgeQueryExecution) {
            $knowledgeSessions = $knowledgeQuery->get()
                ->map(function ($session) use ($language) {

                    $session->banner_image = getImagePathUrl($session->banner_image, 'assets/images');
                    $session->type = 'knowledge_session';

                    $session->title = $language === 'chinese'
                        ? ($session->title_chinese ?? $session->title)
                        : $session->title;

                    $session->description = $language === 'chinese'
                        ? ($session->description_chinese ?? $session->description)
                        : $session->description;

                    $session->category_name = $language === 'chinese'
                        ? (Category::where('id', $session->category_id)->value('category_name_chinese') ??
                            Category::where('id', $session->category_id)->value('category_name'))
                        : Category::where('id', $session->category_id)->value('category_name');

                    unset($session->title_chinese, $session->description_chinese);
                    return $session;
                });
        }

        // VIDEO CONTENT QUERY
        $videoQuery = VideoContent::where('is_featured', 'yes')
            ->whereIn('user_type', ['parent', 'both'])
            ->where('status', 'active')
            ->when($request->category_id, function ($query) use ($request) {
                return $query->where('category_id', $request->category_id);
            })
            ->when($request->search, function ($query) use ($request, $language) {
                return $query->where(function ($q) use ($request, $language) {
                    if ($language === 'chinese') {
                        $q->where('title_chinese', 'like', '%' . $request->search . '%')
                            // ->orWhere('featured_key', 'like', "%$request->search%")
                            ->orWhereRaw(
                                "LOWER(TRIM(featured_key)) LIKE ?",
                                ['%' . strtolower(trim($request->search)) . '%']
                            )
                            ->orWhere('description_chinese', 'like', '%' . $request->search . '%');
                    } else {
                        $q->where('title', 'like', '%' . $request->search . '%')
                            // ->orWhere('featured_key', 'like', "%$request->search%")
                            ->orWhereRaw(
                                "LOWER(TRIM(featured_key)) LIKE ?",
                                ['%' . strtolower(trim($request->search)) . '%']
                            )
                            ->orWhere('description', 'like', '%' . $request->search . '%');
                    }
                });
            });

        switch ($filter) {
            case 'newest':
                $videoQuery->orderBy('created_at', 'desc');
                break;

            case 'favrits':
                $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'favourite')
                        ->where('ulv.user_id', $user_id);
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv.id) as favourite_count'))
                    ->groupBy('video_contents.id')
                    ->having('favourite_count', '>', 0)
                    ->orderByDesc('favourite_count');
                break;

            case 'tranding':
                $videoQuery->join('user_liked_videos as ulv', function ($join) use ($user_id) {
                    $join->on('video_contents.id', '=', 'ulv.video_id')
                        ->where('ulv.type', 'like')
                        ->where('ulv.user_id', $user_id);
                })
                    ->select('video_contents.*', DB::raw('COUNT(ulv.id) as like_count'))
                    ->groupBy('video_contents.id')
                    ->having('like_count', '>', 0)
                    ->orderByDesc('like_count');
                break;

            case 'all':
            default:
                break;
        }

        $videoContents = $videoQuery->get()
            ->map(function ($video) use ($language) {

                $video->type = 'video_content';
                $video->title = $language === 'chinese'
                    ? ($video->title_chinese ?? $video->title)
                    : $video->title;

                $video->description = $language === 'chinese'
                    ? ($video->description_chinese ?? $video->description)
                    : $video->description;

                $video->video_link = getImagePathUrl($video->video_link, 'assets/video');
                $video->thumbnail = getImagePathUrl($video->thumbnail, 'assets/images');

                $video->category_name = $language === 'chinese'
                    ? (Category::where('id', $video->category_id)->value('category_name_chinese') ??
                        Category::where('id', $video->category_id)->value('category_name'))
                    : Category::where('id', $video->category_id)->value('category_name');

                unset($video->title_chinese, $video->description_chinese);

                return $video;
            });

        $mergedData = $knowledgeSessions->merge($videoContents)->values();

        $videoCategory = Category::where('status', 'active')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Featured data fetched successfully!',
            'category' => $videoCategory,
            'data' => $mergedData,
        ], 200);
    }
}
