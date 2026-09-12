<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Models\PermissionUser;
use App\Models\Mood;
use App\Models\ChildMood;
use App\Models\User;
use App\Models\Activity;
use App\Models\BatterySetting;
use Auth;
use Yajra\Datatables\datatables;
use Carbon\Carbon;
use Validator;
use App\Models\ChildPerformedActivity;
use App\Models\Color;
use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\School;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use App\Models\VideoContent;
use App\Models\UserLikedVideo;
use Stichoza\GoogleTranslate\GoogleTranslate;

class MoodTrackerController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesApiUser;

    private $moodTracker = 24;
    private $subadmin_menu_id = 24;
    private $user_attempt_quiz = 25;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.moodTracker.index', compact('pre'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $video_title = VideoContent::where('status', 'active')
                           ->where('mood','mood')
                           ->get();
            // $data['ages'] =  Mood::where('status','1')->get();
            // $data['category'] =  Mood::where('status','active')->get();
            $colors = Color::latest()->get();
            $setting = BatterySetting::where('option_key', 'mood_battery_percentage')->first();
            return view('admin.moodTracker.create', compact('video_title', 'colors', 'setting'));
        }
        return redirect('dashboard');
    }


    public function getList(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $ageGroup = Mood::select('*')->orderBy('id', 'DESC')->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        // $btn .= '<a href="' . route("activity.index", ['id' => $row->id]) . '" class="edit btn btn-info btn-sm">View Activity</a> ';
                        $btn .= '<a href="' . url("edit-mood/" . $row->id) . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("delete-mood/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('name', function ($row) {
                        $plainTexttitle = strip_tags($row->name);
                        $truncatedtitle = substr($plainTexttitle, 0, 30);

                        if (strlen($plainTexttitle) > 30) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('image', function ($row) {

                        $img = '<img src="' . $row['image'] . '" alt="No Preview Image" width="40" height="40">';
                        return $img;
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->editColumn('type', function ($row) {
                        return "<span>" . ucfirst($row->type) . "</span>";
                    })
                    ->addColumn('color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->color . '</span>
                        </div>';
                    })
                    ->rawColumns(['action', 'type', 'color', 'name', 'image', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($ageGroup)
                    ->editColumn('name', function ($row) {
                        $plainTexttitle = strip_tags($row->name);
                        $truncatedtitle = substr($plainTexttitle, 0, 30);

                        if (strlen($plainTexttitle) > 30) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('image', function ($row) {

                        $img = '<img src="' . $row['image'] . '" alt="No Preview Image" width="40" height="40">';
                        return $img;
                    })
                    ->addColumn('color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->color . '</span>
                        </div>';
                    })
                    ->editColumn('type', function ($row) {
                        return "<span class='sts'>" . ucfirst($row->type) . "</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['name', 'type', 'color', 'image',  'status'])
                    ->addIndexColumn()
                    ->make(true);
            }
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            // 'name' => 'required|string|min:3|max:25',
            'name' => [
                'required',
                'min:3',
                'max:25',
                'not_regex:/<[^>]*>/u'
            ],
            // 'name_chinese' => 'required|string|min:3|max:25',
            'color' => 'required',
            'image' => 'required|mimes:jpg,png,jpeg',
            //'points' => 'nullable|numeric|min:0|max:10',
            'type' =>  'required|in:positive,negative',
            'video' => 'required|array',
            'video.*' => 'exists:video_contents,id',
            'comment1' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],
            'comment2' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],
            'comment3' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],

            // 'comment_english' => 'required|string|min:3|max:255',

            'comment_english' => [
                'nullable',
                'min:3',
                // 'max:1000',
                'not_regex:/<[^>]*>/u'
            ],

            // 'comment_chinese' => 'required|string|min:3|max:255',
        ], [
            'name_chinese.required' => 'The name field in chinese is required',
            // 'name_chinese.min' => 'The name field in chinese must not be less than 3 characters.',
            // 'name_chinese.max' => 'The name field in chinese cannot exceed more than 25 characters.',
            'name.required' => 'The name field is required',
            'name.min' => 'The name field must not be less than 3 characters.',
            'name.max' => 'The name field cannot exceed more than 25 characters.',
            'name.color' => 'The Color field is required',
            'comment_english.required' => 'The comment field is required',
            'comment_english.min' => 'The comment field must not be less than 3 characters.',
            'comment_english.max' => 'The comment field cannot exceed more than 255 characters.',
            'comment_english.not_regex' => 'The comment field format is invalid.',
            // 'comment_chinese.required' => 'The comment field in chinese is required',
            // 'comment_chinese.min' => 'The comment field in chinese must not be less than 3 characters.',
            // 'comment_chinese.max' => 'The comment field in chinese cannot exceed more than 255 characters.',
        ]);

        $applyName = null;
        $path = null;
        if ($request->hasFile('image')) {
            $applyName = time() .  $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('assets/avatar'), $applyName);
            $path = asset('assets/avatar/' . $applyName);
        }

        // $referredVideos = [];
        // if ($request->filled('video')) {
        //     $videos = VideoContent::whereIn('id', $request->video)->get();

        //     foreach ($videos as $video) {
        //         $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        //         $title_chinese = $translator->translate($video->title);
        //         $referredVideos[] = [
        //             'id' => $video->id,
        //             'title' => $video->title,
        //             'title_chinese' => $title_chinese ?? $video->title
        //         ];
        //     }
        // }

        $referredVideos = [];
        if ($request->filled('video')) {
            $videos = VideoContent::whereIn('id', $request->video)->get();
            foreach ($videos as $video) {
                $title_chinese = $video->title;
                try {
                    $translator = new GoogleTranslate('zh');
                    $title_chinese = $translator->translate($video->title) ?? $video->title;
                } catch (\Exception $e) {
                    \Log::warning('GoogleTranslate failed for video ' . $video->id . ': ' . $e->getMessage());
                }
                $referredVideos[] = [
                    'id' => $video->id,
                    'title' => $video->title,
                    'title_chinese' => $title_chinese
                ];
            }
        }

        Mood::create([
            'name' => $request->name,
            // 'name_chinese' => $request->name_chinese,
            'image' => $path,
            'status' => 'active',
            'points' => $request->points,
            'color' => $request->color,
            'type' => $request->type,
            'comment_english' => $request->comment_english,
            // 'comment_chinese' => $request->comment_chinese,
            'referred_video' => json_encode($referredVideos),
            'comment1' => $request->comment1,
            'comment2' => $request->comment2,
            'comment3' => $request->comment3,
        ]);

        return redirect()->route('mood-index')->with('success', 'Mood content added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = Mood::where('id', $id)->first();
            $referred_video = VideoContent::where('status', 'active')
                           ->where('mood','mood')
                           ->get();
            $data->referred_video = json_decode($data->referred_video, true);
            $colors = Color::latest()->get();
            $setting = BatterySetting::where('option_key', 'mood_battery_percentage')->first();
            return view('admin.moodTracker.edit', compact('data', 'referred_video', 'colors', 'setting'));
        }
        return redirect('dashboard');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $request->validate([
            // 'name' => 'required|string|min:3|max:25',
            'name' => [
                'required',
                'min:3',
                'max:25',
                'not_regex:/<[^>]*>/u',
            ],
            // 'name_chinese' => 'required|string|min:3|max:25',
            'image' => 'nullable|mimes:jpg,png,jpeg', // <-- Make image optional
            'points' => 'required|numeric|min:0|max:10',
            'color' => 'required',
            'type' =>  'required|in:positive,negative',
            'video' => 'required|array',
            'video.*' => 'exists:video_contents,id',
            // 'comment_english' => 'required|string|min:3|max:255',

            'comment_english' => [
                'nullable',
                'min:3',

                'not_regex:/<[^>]*>/u',
            ],

            'comment1' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],
            'comment2' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],
            'comment3' => ['nullable', 'string', 'min:3', 'not_regex:/<[^>]*>/u'],

            // 'comment_chinese' => 'required|string|min:3|max:255',
        ], [
            // 'name_chinese.required' => 'The name field in chinese is required',
            // 'name_chinese.min' => 'The name field in chinese must not be less than 3 characters.',
            // 'name_chinese.max' => 'The name field in chinese cannot exceed more than 25 characters.',
            'name.required' => 'The name field is required',
            'name.min' => 'The name field must not be less than 3 characters.',
            'name.max' => 'The name field cannot exceed more than 25 characters.',
            'name.color' => 'The color field is required',
            'comment_english.required' => 'The comment field is required',
            'comment_english.min' => 'The comment field must not be less than 3 characters.',
            'comment_english.max' => 'The comment field cannot exceed more than 255 characters.',
            'comment_english.not_regex' => 'The comment field format is invalid.',
            // 'comment_chinese.required' => 'The comment field in chinese is required',
            // 'comment_chinese.min' => 'The comment field in chinese must not be less than 3 characters.',
            // 'comment_chinese.max' => 'The comment field in chinese cannot exceed more than 255 characters.',
        ]);

        $moodTracker = Mood::findOrFail($id);

        if ($request->hasFile('image')) {
            // Delete old image if needed
            if (!empty($moodTracker->image)) {
                $oldImagePath = public_path('assets/avatar/' . basename($moodTracker->image));
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Upload new image
            $applyName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('assets/avatar'), $applyName);
            $moodTracker->image = asset('assets/avatar/' . $applyName);
        }
        // $referredVideos = [];
        // if ($request->filled('video')) {
        //     $videos = \App\Models\VideoContent::whereIn('id', $request->video)->get();
        //     foreach ($videos as $video) {
        //         $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        //         $title_chinese = $translator->translate($video->title);
        //         $referredVideos[] = [
        //             'id' => $video->id,
        //             'title' => $video->title,
        //             'title_chinese' => $title_chinese
        //         ];
        //     }
        // }

        $referredVideos = [];
        if ($request->filled('video')) {
            $videos = \App\Models\VideoContent::whereIn('id', $request->video)->get();
            foreach ($videos as $video) {
                $title_chinese = $video->title; // default fallback
                try {
                    $translator = new GoogleTranslate('zh');
                    $title_chinese = $translator->translate($video->title) ?? $video->title;
                } catch (\Exception $e) {
                    \Log::warning('GoogleTranslate failed for video ' . $video->id . ': ' . $e->getMessage());
                    // fallback already set to original title above
                }
                $referredVideos[] = [
                    'id' => $video->id,
                    'title' => $video->title,
                    'title_chinese' => $title_chinese
                ];
            }
        }

        // Update other fields
        $moodTracker->name = $request->name;
        // $moodTracker->name_chinese = $request->name_chinese;
        $moodTracker->points = $request->points;
        $moodTracker->type = $request->type;
        $moodTracker->comment_english = $request->comment_english;
        // $moodTracker->comment_chinese = $request->comment_chinese;
        $moodTracker->color  = $request->color;
        $moodTracker->referred_video = json_encode($referredVideos);
        $moodTracker->comment1 = $request->comment1;
        $moodTracker->comment2 = $request->comment2;
        $moodTracker->comment3 = $request->comment3;
        $moodTracker->status = $request->status;

        $moodTracker->save();

        return redirect()->route('mood-index')->with('success', 'Mood content updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mood = Mood::find($id);

        if ($mood) {
            $mood->status = 'inactive';
            $mood->save();

            $mood->delete();
        }

        return redirect()->back()->with('success', 'Mood content has been marked as inactive and deleted.');
    }

    // public function getAllMood1(Request $request)
    // {
    //     $child_id = auth()->user()->id;
    //     $data = Mood::with('activity')->where('status', 'active')->get();

    //     $negativeMoods = ChildMood::join('moods', 'child_moods.mood_id', '=', 'moods.id')
    //         ->where('child_moods.child_id', $child_id)
    //         ->where('moods.type', 'negative')
    //         ->orderBy('child_moods.date', 'asc')
    //         ->get(['child_moods.date']);

    //     $consecutiveCount = 0;
    //     $negativeStatus = false;
    //     $previousDate = null;

    //     foreach ($negativeMoods as $mood) {
    //         $currentDate = \Carbon\Carbon::parse($mood->date);


    //         if ($previousDate === null || $previousDate->diffInDays($currentDate) == 1) {
    //             $consecutiveCount++;
    //         } else {

    //             $consecutiveCount = 1;
    //         }


    //         $previousDate = $currentDate;


    //         if ($consecutiveCount >= 4) {
    //             $negativeStatus = true;
    //             break;
    //         }
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'negativeStatus' => $negativeStatus,
    //         'data' => $data,
    //     ], 200);
    // }
    public function getAllMood11(Request $request)
{
    $child_id = auth()->user()->id;

    $data = Mood::with('activity')->where('status', 'active')->get();

    $negativeMoods = \DB::table('child_moods')
        ->join('moods', 'child_moods.mood_id', '=', 'moods.id')
        ->where('child_moods.child_id', $child_id)
        ->whereRaw('LOWER(moods.type) = ?', ['negative'])
        ->where('child_moods.date', '>=', now()->subDays(7)->startOfDay())
        ->whereNull('child_moods.deleted_at')
        ->selectRaw('DISTINCT DATE(child_moods.date) as mood_date')
        ->orderBy('mood_date', 'asc')
        ->get();

    $consecutiveCount = 0;
    $maxStreak = 0;
    $negativeStatus = false;
    $previousDate = null;

    foreach ($negativeMoods as $mood) {
        $currentDate = \Carbon\Carbon::parse($mood->mood_date)->startOfDay();

        if ($previousDate === null) {
            $consecutiveCount = 1;
        } else {
            $diff = $previousDate->diffInDays($currentDate);

            if ($diff == 1) {
                $consecutiveCount++;
            } elseif ($diff > 1) {
                $consecutiveCount = 1;
            }

        }

        $previousDate = $currentDate;

        if ($consecutiveCount > $maxStreak) {
            $maxStreak = $consecutiveCount;
        }

        if ($maxStreak >= 4) {
            $negativeStatus = true;

        }
    }

    // Left hand-rolled deliberately: getAllMood11 is dead - no route points
    // at it (api.php routes get-all-mood to getAllMood below). Migrating dead
    // code only makes it look maintained. @envelope-exempt
    return response()->json([
        'status' => true,
        'message' => 'Data fetched successfully!',
        'negativeStatus' => $negativeStatus,
        'consecutiveDays' => $maxStreak,
        'data' => $data,
    ], 200);
}

public function getAllMood(Request $request)
{
    $child_id = auth()->user()->id;


    $recentMoods = \DB::table('child_moods')
        ->join('moods', 'child_moods.mood_id', '=', 'moods.id')
        ->where('child_moods.child_id', $child_id)
        ->whereNull('child_moods.deleted_at')
        ->select('moods.type', 'child_moods.date', 'moods.name as mood_name')
        ->orderBy('child_moods.id', 'desc')
        ->take(10)
        ->get();

    $consecutiveCount = 0;
    $negativeStatus = false;

    foreach ($recentMoods as $mood) {
        if (strtolower($mood->type) === 'negative') {
            $consecutiveCount++;
        }
        else {

            break;
        }

        if ($consecutiveCount >= 4) {
            $negativeStatus = true;
            break;
        }
    }

    return ApiResponse::success(
        Mood::with('activity')->where('status', 'active')->get(),
        'Data fetched successfully!',
        200,
        [],
        // $extra, not $legacy: these are payload the app reads, not aliases of
        // a canonical key, so a v2 client must keep receiving them.
        ['negativeStatus' => $negativeStatus, 'consecutiveDays' => $consecutiveCount]
    );
}
    public function storeChildMood(\App\Http\Requests\Api\StoreChildMoodRequest $request)
    {
        // child_id used to come straight from the body, letting any caller wipe
        // and rewrite another child's mood history and loyalty points below.
        $child_id = $this->resolveTargetUserId($request, 'child_id');

        if (!$child_id) {
            return $this->unauthorisedTargetResponse($request->language ?? 'english');
        }

        $mood_id = $request->mood_id;
        $mood_name = $request->mood_name;
        $points = $request->points ?? 0;
        $todayChildRecord = ChildMood::where('child_id', $child_id)
            ->where('date', Carbon::now()->toDateString())
            ->first();

        // Re-recording today's mood debits points and deletes the day's
        // activities and mood row before recreating it; a failure between those
        // steps used to leave the child with the points already deducted and no
        // mood at all.
        $data = \DB::transaction(function () use ($todayChildRecord, $child_id, $mood_id, $mood_name, $points) {
            if ($todayChildRecord) {
                $activityIds = ChildPerformedActivity::where('child_id', $child_id)
                    ->whereDate('created_at', Carbon::today())
                    ->pluck('activity_id')
                    ->toArray();
                $points = Activity::whereIn('id', $activityIds)->sum('points');

                $user = User::find($child_id);
                $user->update([
                    'loyalty_points' => max(0, $user->loyalty_points - $points)
                ]);

                ChildPerformedActivity::where('child_id', $child_id)
                    ->whereDate('created_at', Carbon::today())
                    ->delete();

                $todayChildRecord->delete();
            }

            $created = ChildMood::create([
                'child_id' => $child_id,
                'mood_id' => $mood_id,
                'mood_name' => $mood_name,
                'points' => $points,
                'date' => Carbon::now()->format('Y-m-d')
            ]);

            User::where('id', $child_id)->update(['is_mood_updated' => 'yes']);

            return $created;
        });

        if ($data) {
            $payload = $data->toArray();
            $payload['referred_video'] = [];

            try {
                $negativeMoodIds = Mood::where('type', 'negative')->pluck('id');
                $requiredDates = collect(range(1, 5))->map(function ($i) {
                    return Carbon::now()->subDays($i)->toDateString();
                });
                $fromDate = Carbon::now()->subDays(5)->startOfDay()->format('Y-m-d');

                $childMoodEntries = ChildMood::where('child_id', $child_id)
                    ->whereIn('mood_id', $negativeMoodIds)
                    ->where('date', '>=', $fromDate)
                    ->get();

                $datesWithNegativeMood = $childMoodEntries
                    ->pluck('date')
                    ->map(fn ($date) => $this->moodDateToString($date))
                    ->filter()
                    ->unique();

                if ($requiredDates->diff($datesWithNegativeMood)->isEmpty()) {
                    $payload['negative_mood'] = 'yes';
                }

                $payload['referred_video'] = $this->decodeReferredVideo(
                    Mood::where('id', $mood_id)->value('referred_video')
                );
            } catch (\Throwable $e) {
                \Log::error('storeChildMood extras failed after save', [
                    'child_id' => $child_id,
                    'mood_id' => $mood_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $language = $request->language ?? 'english';

            return ApiResponse::success(
                $payload,
                $language === 'english' ? 'Data stored successfully!' : '数据存储成功！',
                200
            );
        } else {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => $request->language == 'english' ? 'Data not stored' : '数据未存储',
                'data' => null
            ], 200);
        }
    }
    public function getChildMood(Request $request)
    {
        $language = $request->language ?? 'english';
        $child_id = auth()->user()->id;
        $filter = $request->filter_type;

        $query = ChildMood::where('child_id', $child_id)
            ->whereNull('deleted_at');

        // Apply date filter
        if ($filter === 'last_week') {
            $query->where('created_at', '>=', Carbon::now()->subWeek());
        } elseif ($filter === 'last_month') {
            $query->where('created_at', '>=', Carbon::now()->subMonth());
        } elseif ($filter === 'last_3_month') {
            $query->where('created_at', '>=', Carbon::now()->subMonths(3));
        } elseif ($filter === 'last_6_month') {
            $query->where('created_at', '>=', Carbon::now()->subMonths(6));
        } else {
            $query->where('created_at', '>=', Carbon::now()->subWeek());
        }

        $child_mood_details = $query->get()->map(function ($mood_details) use ($language) {
            $mood = Mood::find($mood_details->mood_id);

            $mood_details->image = $mood->image;
            $mood_details->mood_name = $language === 'chinese'
                ? ($mood->name_chinese ?? $mood->name)
                : $mood->name;
            $mood_details->points = $mood->points;
            $mood_details->comment = $language === 'chinese'
                ? ($mood->comment_chinese ?? $mood->comment_english)
                : $mood->comment_english;
            $mood_details->comment1 = $mood->comment1;
            $mood_details->comment2 = $mood->comment2;
            $mood_details->comment3 = $mood->comment3;
            return $mood_details;
        });

        if ($child_mood_details->isNotEmpty()) {
            return ApiResponse::success($child_mood_details->values(), $language == 'english' ? 'Details fetched successfully!' : '详细信息获取成功！', 200);
        } else {
            return ApiResponse::success([], $language == 'english' ? 'Details not found' : '未找到详细信息', 200);
        }
    }


//     public function moodTracker1(Request $request)
//     {
//         $language = $request->language ?? 'english';
//         $child_id = auth()->user()->id;
//         $month = $request->month ?? Carbon::now()->month;
//         $year = $request->year ?? Carbon::now()->year;

//         $startDate = Carbon::create($year, $month, 1);
//         $endDate = $startDate->copy()->endOfMonth();

//         // Fetch moods for selected month
//         $entries = ChildMood::where('child_id', $child_id)
//             ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
//             ->whereNull('deleted_at')
//             ->latest()->get();

//         $moodFrequency = [];
//         $calendarData = [];

//         // Build mood entries by date
//         foreach ($entries as $entry) {
//             $mood = Mood::find($entry->mood_id);

//             $moodName = $language === 'chinese'
//                 ? ($mood->name_chinese ?? $mood->name)
//                 : $mood->name;

//             $calendarData[$entry->date] = [
//                 'image' => $mood->image,
//                 'mood_name' => $moodName,
//                 'points' => $mood->points,
//                 'color' => $mood->color ?? null,
//                 'comment' => $language === 'chinese'
//                     ? ($mood->comment_chinese ?? $mood->comment_english)
//                     : $mood->comment_english,
//             ];

//             // Count mood frequency
//             if (isset($moodFrequency[$moodName])) {
//                 $moodFrequency[$moodName]['count'] += 1;
//             } else {
//                 $moodFrequency[$moodName] = [
//                     'count' => 1,
//                     'image' => $mood->image,
//                     'color' => $mood->color ?? null,
//                 ];
//             }
//         }

//         // Fill missing dates
//         $allDates = [];
//         $current = $startDate->copy();
//         while ($current->lte($endDate)) {
//             $dateStr = $current->toDateString();
//             $allDates[$dateStr] = $calendarData[$dateStr] ?? [
//                 'image' => null,
//                 'mood_name' => null,
//                 'points' => null,
//                 'comment' => null,
//                 'color' => null, // default neutral/gray color
//             ];
//             $current->addDay();
//         }

//         // Mood ring
//         // $moodRing = [];
//         // foreach ($moodFrequency as $moodName => $data) {
//         //     $moodRing[] = [
//         //         'mood_name' => $moodName,
//         //         'count' => $data['count'],
//         //         'image' => $data['image'],
//         //         'color' => $data['color'],
//         //     ];
//         // }

//         // Mood ring sorted by count (highest first)
//         // $moodRing = collect($moodFrequency)
//         //     ->sortByDesc('count')
//         //     ->map(function ($item, $moodName) {
//         //         return [
//         //             'mood_name' => $moodName,
//         //             'count' => $item['count'],
//         //             'image' => $item['image'],
//         //             'color' => $item['color'] ?? '#CCCCCC',
//         //         ];
//         //     })->values()->all();

//         // Group moodFrequency by color
//         $groupedByColor = [];

//         // First, group mood frequency by color

//         // Convert to array and sort by total_count descending
//         $moodRing = collect($groupedByColor)
//             ->sortByDesc('total_count')
//             ->values()
//             ->all();


//         // Top 3 moods
//         $topEmotions = collect($moodFrequency)
//             ->sortByDesc('count')
//             ->take(3)
//             ->map(function ($item, $moodName) {
//                 return [
//                     'mood_name' => $moodName,
//                     'count' => $item['count'],
//                     'image' => $item['image'],
//                     'color' => $item['color'],
//                 ];
//             })->values();

//         $entries = ChildMood::where('child_id', $child_id)
//         ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
//         ->whereNull('deleted_at')
//         ->get();

//     $moodFrequency = [];

//     foreach ($entries as $entry) {
//         $mood = Mood::find($entry->mood_id);
//         if ($mood) {
//             $moodName = ($language === 'chinese') ? ($mood->name_chinese ?? $mood->name) : $mood->name;

//             if (isset($moodFrequency[$moodName])) {
//                 $moodFrequency[$moodName]['count'] += 1;
//             } else {
//                 $moodFrequency[$moodName] = [
//                     'count' => 1,
//                     'image' => $mood->image,
//                     'color' => $mood->color,
//                 ];
//             }
//         }
//         }

//     $allCategories = Category::where('status', 'active')->get();
//     $categoriesData = [];

//     foreach ($allCategories as $category) {
//         $catName = $language === 'chinese'
//             ? ($category->category_name_chinese ?? $category->category_name)
//             : $category->category_name;

//         $mappedMoods = [];

//         foreach ($moodFrequency as $moodName => $data) {
//             $moodCheck = Mood::where('image', $data['image'])
//                              ->where('color', $category->color)
//                              ->first();

//             if ($moodCheck) {
//                 $mappedMoods[] = [
//                     'mood_id'   => $moodCheck->id,
//                     'mood_name' => $moodName,
//                     'count'     => $data['count'],
//                     'image'     => $data['image']
//                 ];
//             }
//         }

//         $categoriesData[] = [
//             'category_id'   => $category->id,
//             'category_name' => $catName,
//             'color'         => $category->color,
//             'moods'         => $mappedMoods
//         ];
//     }

//         return response()->json([
//             'calendar_data' => $allDates,
//             'mood_ring' => $moodRing,
//             'top_emotions' => $topEmotions,
//             'categories'    => $categoriesData,
//             'status' => true,
//             'message' => $language === 'english'
//                 ? 'Mood data fetched successfully!'
//                 : '成功获取心情数据！',
//         ]);
//    }
   public function moodTracker(Request $request)
{
    $language = $request->language ?? 'english';
    $child_id = auth()->user()->id;

    $month = $request->month ?? Carbon::now()->month;
    $year = $request->year ?? Carbon::now()->year;

    $startDate = Carbon::create($year, $month, 1);
    $endDate = $startDate->copy()->endOfMonth();

    $entries = ChildMood::with('mood')
        ->where('child_id', $child_id)
        ->whereDate('date', '>=', $startDate)
        ->whereDate('date', '<=', $endDate)
        ->get();

    $moodFrequency = [];
    $calendarData = [];

    foreach ($entries as $entry) {
        $mood = $entry->mood;

        if (!$mood) continue;

        $moodName = $language === 'chinese'
            ? ($mood->name_chinese ?? $mood->name)
            : $mood->name;

        $calendarData[$entry->date] = [
            'image' => $mood->image,
            'mood_name' => $moodName,
            'points' => $mood->points,
            'color' => $mood->color ?? null,
            'comment' => $language === 'chinese'
                ? ($mood->comment_chinese ?? $mood->comment_english)
                : $mood->comment_english,
        ];

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

    $allDates = [];
    $current = $startDate->copy();

    while ($current->lte($endDate)) {
        $dateStr = $current->toDateString();

        $allDates[$dateStr] = $calendarData[$dateStr] ?? [
            'image' => null,
            'mood_name' => null,
            'points' => null,
            'comment' => null,
            'color' => null,
        ];

        $current->addDay();
    }

   $groupedByColor = [];

foreach ($moodFrequency as $moodName => $data) {
    $color = $data['color'] ?? '#CCCCCC';

    if (!isset($groupedByColor[$color])) {
        $groupedByColor[$color] = [
            'color' => $color,
            'mood_name' => [],
            'total_count' => 0,
        ];
    }

    // 👉 unique mood name add karo (repeat nahi)
    if (!in_array($moodName, $groupedByColor[$color]['mood_name'])) {
        $groupedByColor[$color]['mood_name'][] = $moodName;
    }

    $groupedByColor[$color]['total_count'] += $data['count'];
}

$moodRing = collect($groupedByColor)
    ->map(function ($item) {
        // 👉 agar sirf 1 mood hai → string bana do
        if (count($item['mood_name']) === 1) {
            $item['mood_name'] = $item['mood_name'][0];
        }
        return $item;
    })
    ->sortByDesc('total_count')
    ->values()
    ->all();

    $topEmotions = collect($moodFrequency)
        ->sortByDesc('count')
        ->take(3)
        ->map(function ($item, $moodName) {
            return [
                'mood_name' => $moodName,
                'count' => $item['count'],
                'image' => $item['image'],
                'color' => $item['color'],
            ];
        })
        ->values();

    $allCategories = Category::where('status', 'active')->get();
    $categoriesData = [];

    foreach ($allCategories as $category) {

        $catName = $language === 'chinese'
            ? ($category->category_name_chinese ?? $category->category_name)
            : $category->category_name;

        $mappedMoods = [];

        foreach ($moodFrequency as $moodName => $data) {

            $moodCheck = Mood::where('image', $data['image'])
                ->where('color', $category->color)
                ->first();

            if ($moodCheck) {
                $mappedMoods[] = [
                    'mood_id'   => $moodCheck->id,
                    'mood_name' => $moodName,
                    'count'     => $data['count'],
                    'image'     => $data['image']
                ];
            }
        }

        $categoriesData[] = [
            'category_id'   => $category->id,
            'category_name' => $catName,
            'color'         => $category->color,
            'moods'         => $mappedMoods
        ];
    }

    return ApiResponse::success(
        [
            'calendar_data' => $allDates,
            'mood_ring' => $moodRing,
            'top_emotions' => $topEmotions,
            'categories' => $categoriesData,
        ],
        $language === 'english'
            ? 'Mood data fetched successfully!'
            : '成功获取心情数据！',
        200,
        // v1 reads all four at the top level.
        [
            'calendar_data' => $allDates,
            'mood_ring' => $moodRing,
            'top_emotions' => $topEmotions,
            'categories' => $categoriesData,
        ]
    );
}

    public function activityIndex($id)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.activity.index', compact('pre', 'id'));
    }


    public function activityList(Request $request, $id)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $questions = Activity::where('mood_id', $id)->orderBy('id', 'DESC')->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($questions)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) use ($id) {
                        $btn = "";
                        $btn .= '<a href="' . url('edit-activity/' . $row->id) . '" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a> ';

                        $btn .= '<a href="' . url("delete-activity/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('category', function ($row) {
                        $categoryNames = Mood::where('id', $row->mood_id)->value('name');
                        $plainTexttitle = strip_tags($categoryNames);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('activity', function ($row) {
                        $plainTextDescription = strip_tags($row->name);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })

                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['action', 'category', 'activity', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($questions)
                    ->addIndexColumn()
                    ->editColumn('category', function ($row) {
                        $categoryNames = Mood::where('id', $row->mood_id)->value('name');
                        $plainTexttitle = strip_tags($categoryNames);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('activity', function ($row) {
                        $plainTextDescription = strip_tags($row->name);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })


                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['category', 'question', 'status'])
                    ->make(true);
            }
        }
    }


    // public function activity(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $child_id = auth()->user()->id;

    //     // Get today's mood_id for the child
    //     $mood_id = ChildMood::where('child_id', $child_id)
    //         ->where('date', Carbon::now()->format('Y-m-d'))
    //         ->value('mood_id');

    //     if (!$mood_id) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'No mood selected for today',
    //             'data' => []
    //         ]);
    //     }

    //     // Fetch activities for that mood
    //     $activities = Activity::where('mood_id', $mood_id)
    //         ->where('status', 'active')
    //         ->get();

    //     // Get referred video
    //     $referred_video = Mood::where('id', $mood_id)->value('referred_video');
    //     $decodedVideo = json_decode($referred_video, true);

    //     // Prepare activities
    //     $activityData = collect(); // initialize empty collection
    //     if (!$activities->isEmpty()) {
    //         $activityData = $activities->map(function ($activity) use ($language, $child_id) {
    //             $activityName = $language === 'chinese' ? $activity->name_chinese : $activity->name;

    //             $isPerformed = ChildPerformedActivity::where('child_id', $child_id)
    //                 ->where('activity_id', $activity->id)
    //                 ->whereDate('created_at', Carbon::now()->toDateString())
    //                 ->exists();

    //             return [
    //                 'id' => $activity->id,
    //                 'activity_name' => $activityName,
    //                 'mood_id' => $activity->mood_id,
    //                 'points' => $activity->points,
    //                 'status' => $activity->status,
    //                 'is_performed' => $isPerformed,
    //                 'type' => 'activity'
    //             ];
    //         });
    //     }

    //     // Prepare video data with translated titles
    //     $videoData = collect();

    //     if (!empty($decodedVideo) && is_array($decodedVideo)) {
    //         foreach ($decodedVideo as $video) {
    //             $videoData->push([
    //                 'id' => $video['id'] ?? null,
    //                 'title' => $language === 'chinese' ? ($video['title_chinese'] ?? '') : ($video['title'] ?? ''),
    //                 'type' => 'video',
    //                 'video_url' => isset($video['video_link']) ? asset('assets/video/' . $video['video_link']) : null,
    //                 "ddd" => $video
    //             ]);
    //         }
    //     }

    //     // Merge both collections
    //     $mergedData = $activityData->merge($videoData);

    //     return response()->json([
    //         'status' => true,
    //         'message' => $language == 'english' ? 'Data fetched successfully!' : '数据获取成功！',
    //         'data' => $mergedData
    //     ]);
    // }

    public function activity(Request $request)
    {
        $language = $request->language ?? 'english';
        $child_id = auth()->user()->id;

        // Get today's mood_id for the child
        $mood_id = ChildMood::where('child_id', $child_id)
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->value('mood_id');

        if (!$mood_id) {
            return ApiResponse::success([], 'No mood selected for today', 200);
        }

        // Fetch activities for that mood
        $activities = Activity::where('mood_id', $mood_id)
            ->where('status', 'active')
            ->get();

        // Prepare activities
        $activityData = collect();
        if (!$activities->isEmpty()) {
            $activityData = $activities->map(function ($activity) use ($language, $child_id) {
                $activityName = $language === 'chinese' ? $activity->name_chinese : $activity->name;

                $isPerformed = ChildPerformedActivity::where('child_id', $child_id)
                    ->where('activity_id', $activity->id)
                    ->whereDate('created_at', Carbon::now()->toDateString())
                    ->exists();

                return [
                    'id' => $activity->id,
                    'activity_name' => $activityName,
                    'mood_id' => $activity->mood_id,
                    'points' => $activity->points,
                    'status' => $activity->status,
                    'is_performed' => $isPerformed,
                    'type' => 'activity'
                ];
            });
        }

        // Get referred video IDs
        $referred_video = Mood::where('id', $mood_id)->value('referred_video');
        $decodedVideo = json_decode($referred_video, true);
        $videoData = collect();

        if (!empty($decodedVideo) && is_array($decodedVideo)) {
            $videoIds = array_column($decodedVideo, 'id');

            $videos = VideoContent::whereIn('id', $videoIds)->get();

            $videoData = $videos->map(function ($video) use ($language) {

                return [
                    'id' => $video->id,
                    'title' => $language === 'chinese' ? ($video->title_chinese ?? $video->title) : $video->title,
                    'description' => $language === 'chinese' ? ($video->description_chinese ?? $video->description) : $video->description,
                    'type' => 'video',
                    'video_url' => getImagePathUrl($video->video_link, 'assets/video'),
                    'thumbnail' => getImagePathUrl($video->thumbnail, 'assets/images'),
                    'last_watched_duration' => $video->last_watched_duration,
                    'video_duration' => $video->video_duration,
                    'points' => $video->points,
                    'user_type' => $video->user_type,
                    'age' => $video->age,
                    'title_color' => $video->title_color,
                    'color' => $video->color,
                ];
            });
        }

        // Merge both collections
        $mergedData = $activityData->merge($videoData);

        return ApiResponse::success($mergedData, $language == 'english' ? 'Data fetched successfully!' : '数据获取成功！', 200);
    }





    public function addActivity($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['title'] = 'Add Question';
            $data['category'] =  Mood::where('status', 'active')->get();
            $data['id'] = $id;
            return view('admin.activity.create')->with($data);
        }
        return redirect('home');
    }

    public function storeActivity(Request $request, $category_id)
    {
        $rules = [
            'category' => 'required|exists:moods,id',
            'points' => 'required|numeric|min:1|max:100',
        ];

        $rules['activity'] = 'required|string|min:3|max:100|not_regex:/<[^>]*>/u';

        // $rules['activity_chinese'] = 'required|string|min:3|max:100';

        $messages = [
            'category.required' => 'The mood is required.',
            'category.exists' => 'The mood does not exist.',
            'points.required' => 'Points are required.',
            'points.numeric' => 'Points must be a number.',
            'points.min' => 'Points must be at least 1.',
            'activity.required' => 'The activity name field is required.',
            'activity.string' => 'The activity name must be a valid text.',
            'activity.min' => 'The activity name must not be less than 3 characters.',
            'activity.max' => 'The activity name cannot exceed 100 characters.',
            // 'activity_chinese.required' => 'The activity name field in chinese is required.',
            // 'activity_chinese.string' => 'The activity name must be a valid text.',
            // 'activity_chinese.min' => 'The activity name must not be less than 3 characters.',
            // 'activity_chinese.max' => 'The activity name cannot exceed 100 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        Activity::create([
            'mood_id' => $request['category'],
            'name' => $request['activity'],
            // 'name_chinese' => $request['activity_chinese'],
            'points' => $request['points'],
            'status' => 'active'

        ]);
        return redirect()->route('activity.index', ['id' => $category_id])->with('added', 'Activity Added Successfully!');
    }

    public function editActivity($id)
    {
        // dd($id);
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['question'] = Activity::where('id', $id)->first();
            $data['category'] =  Mood::where('status', 'active')->get();
            $data['id'] = $data['question']->id;
            return view('admin.activity.edit')->with($data);
        }
        return redirect('dashboard');
    }

    public function updateActivity(Request $request, $id)
    {
        // dd($id);
        $rules = [
            'points' => 'required|numeric|min:1|max:100',
            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'not_regex:/<[^>]*>/',
                \Illuminate\Validation\Rule::unique('activities')
                    ->where('mood_id', $request->category)
                    ->ignore($id)->whereNull('deleted_at')
            ],
            // 'name_chinese' => [
            //     'required',
            //     'string',
            //     'min:3',
            //     'max:100',
            //     \Illuminate\Validation\Rule::unique('activities')
            //         ->where('mood_id', $request->category)
            //         ->ignore($id)->whereNull('deleted_at')
            // ],
            'status' => 'required|in:active,inactive',
        ];


        $messages = [
            'points.required' => 'Points are required.',
            'points.numeric' => 'Points must be a number.',
            'points.min' => 'Points must be at least 1.',
            'activity.required' => 'The activity name field is required.',
            'name.required' => 'The activity name field is required.',
            'name.string' => 'The activity name must be a valid text.',
            'name.unique' => 'The activity name already exists in this category.',
            'name.min' => 'The activity name must not be less than 3 characters.',
            'name.max' => 'The activity name cannot exceed 100 characters.',
            // 'name_chinese.required' => 'The activity name field in chinese is required.',
            // 'name_chinese.string' => 'The activity name must be a valid text.',
            // 'name_chinese.unique' => 'The activity name already exists in this category.',
            // 'name_chinese.min' => 'The activity name must not be less than 3 characters.',
            // 'name_chinese.max' => 'The activity name cannot exceed 100 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $update = [
            'mood_id' => $request['category'],
            'name' => $request['name'],
            'name_chinese' => $request['name_chinese'],
            'points' => $request['points'],
            'status' => $request['status'],
        ];
        Activity::where('id', $id)->update($update);
        return redirect()->route('activity.index', ['id' => $request['category']])->with('updated', 'Activity Updated Successfully!');
    }
    public function deleteActivity($id)
    {
        $activity = Activity::find($id);
        if ($activity) {
            $activity->status = 'inactive';
            $activity->save();

            $activity->delete();
        }

        return redirect()->back()->with('success', 'Activity has been marked as inactive and soft deleted.');
    }
    public function storeChildActivity(Request $request)
    {
        // $language = $request->language ?? 'english';

        // $activity = Activity::find($request->activity_id);
        // if (!$activity) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Invalid activity ID.'
        //     ], 200);
        // }
        // ChildPerformedActivity::create([
        //     'child_id' => auth()->user()->id,
        //     'activity_id' => $activity->id,
        //     'points' => $request->points
        // ]);
        // $user_details = User::where('id', auth()->user()->id)->first();
        // $user_details->loyalty_points += $request->points;
        // $user_details->save();
        // return response()->json([
        //     'status' => true,
        //     'message' => $language == 'english' ? 'Data fetched successfully!' : '数据获取成功！',
        // ]);

        $user = auth()->user();

        $request->validate([
            'mood_id' => 'required',
            'mood_name' => 'required|string|max:255',
            'point' => 'required|integer|min:0',
            'date' => 'required',
        ]);
        $date =  Carbon::parse($request->date);
        $data = [
            'child_id' => $user->id,
            'mood_id' => $request->mood_id,
            'mood_name' => $request->mood_name,
            'points' => $request->point,
            'date' => $request->date,
        ];

        ChildMood::create($data);

        try {
            $mood_battery_value = BatterySetting::where('option_key', 'mood_battery_percentage')->first();
            $points = (int) ($mood_battery_value ? $mood_battery_value->option_value : 10);
            battery_credit_once_per_day($user, $points, 'mood', [
                'mood_id' => $request->mood_id,
                'mood_name' => $request->mood_name
            ], $date);

            $content = getNotificationContent('mood_update', [
                'mood_name' => $request->mood_name,
                'points'    => $request->point
            ]);

            $notification_type = "mood_update";
            $extra_data = [
                "mood_id"   => $request->mood_id,
                "mood_name" => $request->mood_name,
                "points"    => $request->point,
                "type"      => "mood_update"
            ];
            $user_type = "user";
            $checkUser = User::where('id', $user->id)
                ->first();

            if ($checkUser) {
                $device = DeviceToken::where('user_id', $checkUser->id)
                    ->whereNotNull('token')
                    ->first();

                if ($device) {
                    sendNotificationSender(
                        $device->user_id,
                        $content['title'],
                        $content['body'],
                        $notification_type,
                        $extra_data,
                        $user_type
                    );
                }
            }
        } catch (\Throwable $e) {
            \Log::error('storeChildActivity extras failed after save', [
                'child_id' => $user->id,
                'mood_id' => $request->mood_id,
                'error' => $e->getMessage(),
            ]);
        }

        return ApiResponse::success(null, 'Child Mood stored successfully!');
    }

    public function getSuggestedActivity(Request $request)
    {
        $child_id = auth()->user()->id;
        $activity_id = $request->activity_id;

        $childPerformedActivity = ChildPerformedActivity::where([
            'child_id' => $child_id,
            'activity_id' => $activity_id
        ])->first();

        if (!$childPerformedActivity) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'Activity not performed by child.',
                'data' => null,
            ], 200);
        }

        $activity = Activity::find($childPerformedActivity->activity_id);

        if (!$activity) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'Activity not found.',
                'data' => null,
            ], 200);
        }

        $mood = Mood::find($activity->mood_id);

        // Decode referred_video JSON
        $referredVideos = json_decode($mood->referred_video, true);
        $videoIds = array_column($referredVideos, 'id');

        // Fetch only required fields
        $videos = VideoContent::whereIn('id', $videoIds)->get()->map(function ($video) {
            return [
                'video_id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'last_watched_duration' => $video->last_watched_duration,
                'user_type' => $video->user_type,
                'age' => $video->age,
                'title_color' => $video->title_color,
                'color' => $video->color,
                'points' => $video->points,
                'is_featured' => $video->is_featured,
                'video_link' => getImagePathUrl($video->video_link, 'assets/video'),
                'thumbnail' => getImagePathUrl($video->thumbnail, 'assets/images'),
            ];
        });

        $moodData = $mood->toArray();
        $moodData['videos'] = $videos;

        return ApiResponse::success($moodData, 'Get Suggested Activity Data.', 200);
    }




    public function childSupport(Request $request)
    {
        $type = $request->type;
        $language = $request->language ?? 'english';

        if ($type == 'parent') {
            $user_details =  User::where('id', auth()->user()->id)->first();
            $parent_details =  User::where('id', $user_details->parent_id)->where('status', 'active')->latest()->first();

            $title = 'Child Support';
            $message = $user_details->name . ' has requested support. Please check your dashboard for details.';

            $emailData = [
                'user_name' => $user_details->name,
                'parent_name' => $parent_details->name,
            ];
            ___mail_sender($parent_details->email, 'child_support_to_parent', $emailData, 'english');
            sendNotification(
                $user_details->parent_id,
                $title,
                $message,
                $user_details,
                'child_support'
            );
            return ApiResponse::success(
                null,
                $language == 'english' ? 'Mail send to parent' : '数据获取成功！'
            );
        } else {
            $user =  User::where('id', auth()->user()->id)->first();
            $user_details = User::where('id', $user->parent_id)->first();

            // A caller with no parent_id (a parent or teacher hitting this
            // branch) left $user_details null and 500'd on ->school_id.
            $school_details = $user_details
                ? School::where('id', $user_details->school_id)->where('status', 'active')->latest()->first()
                : null;
            if (!$school_details) {
                return ApiResponse::error(
                    $language == 'english' ? 'School not found.' : '未找到学校。',
                    200
                );
            }
            $emailData = [
                'parent_name' => $user_details->name,
                'parent_email' => $user_details->email,
                'user_name' => $user->name,
                'school_name' => $school_details->name,
            ];
            // dd($emailData,$school_details->email);
            ___mail_sender($school_details->email, 'child_support', $emailData, 'english');
            return ApiResponse::success(
                null,
                $language == 'english' ? 'Mail send to school' : '数据获取成功！'
            );
        }
    }

    public function childMoodTrackerList(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->user_attempt_quiz);
        if (!isset($pre) || empty($pre)) {
            return redirect('home');
        }
        $data['title'] = "Child Mood Tracker";
        // $user_list = User::pluck('id');
        $attempts = ChildMood::with('user')
            ->select('child_moods.*')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('child_moods')
                    ->groupBy('child_id');
            })
            ->whereHas('user', function ($q) {
                $q->whereNotNull('name');
            })
            ->orderBy('id', 'desc')
            ->get();

        if ($request->ajax()) {
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return Datatables::of($attempts)
                    ->addIndexColumn()
                    ->addColumn('action', function ($attempt) {
                        $btn = '<a href="' . url('child-mood-tracker-details/' . $attempt['child_id'] . '/' . $attempt['quiz_category_id']) . '" class="edit btn btn-outline-success btn-sm" title="View Details">
                                    <i class="mdi mdi-eye"></i> Details
                                </a>';
                        return $btn;
                    })

                    ->editColumn('user', function ($attempt) {
                        return $attempt->user && $attempt->user->name
                            ? ucfirst($attempt->user->name)
                            : 'N/A';
                    })
                    ->editColumn('user_name', function ($attempt) {
                        return $attempt->user && $attempt->user->username
                            ? $attempt->user->username
                            : 'N/A';
                    })

                    ->rawColumns(['action', 'user_name'])
                    ->make(true);
            } else {
                return Datatables::of($attempts)
                    ->addIndexColumn()
                    ->editColumn('user_name', function ($attempt) {
                        return $attempt['user']['name'] ?? 'N/A';
                    })
                    ->rawColumns(['user_name', 'user'])
                    ->make(true);
            }
        }
        return view('admin.child-mood-tracker.index')->with($data);
    }

    // public function childMoodTrackerDetails($user_id)
    // {
    //     $pre = PermissionUser::checkpermission(Auth::user()->id, $this->user_attempt_quiz);

    //     if (!empty($pre) && $pre->is_view === 'yes') {
    //         $data['title'] = 'Child Mood Tracker Details';

    //         $moods = Mood::where('status', 'active')->get();

    //         if ($moods->isEmpty()) {
    //             return redirect('dashboard')->with('error', 'No mood records found.');
    //         }

    //         $data['moodDetails'] = [];

    //         foreach ($moods as $mood) {
    //             // Get child mood entries for this mood
    //             $moodEntries = ChildMood::where('child_id', $user_id)
    //                 ->where('mood_id', $mood->id)
    //                 ->get();
    //             $totalMoodPoints = 0;
    //             $moodActivityDetails = [];
    //             // dd($moodEntries);
    //             foreach ($moodEntries as $entry) {
    //                 $totalMoodPoints += $entry->points;

    //                 // Get all activities by child, regardless of mood (since not linked)
    //                 $activities = ChildPerformedActivity::where('child_id', $user_id)->get();
    //                 foreach ($activities as $activity) {
    //                     // dd($entry->mood_id);
    //                     // dd($activity->activity_id);
    //                     $activityInfo = Activity::where('id', (int)$activity->activity_id)->where('mood_id', $entry->mood_id)->where('status', 'active')->first();
    //                     // dd($activityInfo);
    //                     if ($activityInfo) {
    //                         $moodActivityDetails[] = [
    //                             'activity_name' => $activityInfo->name ?? 'N/A',
    //                             'points' => $activityInfo->points,
    //                         ];
    //                     }
    //                 }
    //             }

    //             $data['moodDetails'][] = [
    //                 'mood_name' => $mood->name,
    //                 'mood_image' => $mood->image,
    //                 'mood_points' => $totalMoodPoints,
    //                 'activities' => $moodActivityDetails,
    //             ];
    //         }
    //         return view('admin.child-mood-tracker.details', $data);
    //     }

    //     return redirect('dashboard')->with('error', 'Unauthorized access.');
    // }
    public function childMoodTrackerDetails(Request $request, $user_id)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startDate = Carbon::create($year, $month)->startOfMonth();
        $endDate = Carbon::create($year, $month)->endOfMonth();

        $entries = ChildMood::where('child_id', $user_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->get();

        $moodFrequency = [];
        $calendarData = [];

        foreach ($entries as $entry) {
            $mood = Mood::find($entry->mood_id);

            $calendarData[$entry->date] = [
                'image' => $mood->image,
                'mood_name' => $mood->name,
                'points' => $mood->points,
                'color' => $mood->color ?? null,
                'comment' => $mood->comment_english,
            ];

            if (isset($moodFrequency[$mood->name])) {
                $moodFrequency[$mood->name]['count'] += 1;
            } else {
                $moodFrequency[$mood->name] = [
                    'count' => 1,
                    'image' => $mood->image,
                    'color' => $mood->color ?? null,
                ];
            }
        }

        // Fill all days in the month
        $allDates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();
            $allDates[$dateStr] = $calendarData[$dateStr] ?? [
                'image' => null,
                'mood_name' => null,
                'points' => null,
                'comment' => null,
                'color' => null,
            ];
            $current->addDay();
        }

        // Group moodFrequency by color
        $groupedByColor = [];
        foreach ($moodFrequency as $moodName => $data) {
            $color = $data['color'] ?? '#CCCCCC';
            if (!isset($groupedByColor[$color])) {
                $categoryName = isset($colorCategoryMap[$color])
                 ? ($language === 'chinese'
                 ? $colorCategoryMap[$color]['chinese']
                 : $colorCategoryMap[$color]['english'])
                 : 'Other';
                $groupedByColor[$color] = [
                    'color' => $color,
                    'category_name' => $categoryName,
                    'total_count' => 0,
                    'moods' => []
                ];
            }

            $groupedByColor[$color]['total_count'] += $data['count'];
            $groupedByColor[$color]['moods'][] = [
                'mood_name' => $moodName,
                'count' => $data['count'],
                'image' => $data['image'],
            ];
        }

        $moodRing = collect($groupedByColor)->sortByDesc('total_count')->values()->all();

        $topEmotions = collect($moodFrequency)
            ->sortByDesc('count')
            ->take(3)
            ->map(function ($item, $moodName) {
                return [
                    'mood_name' => $moodName,
                    'count' => $item['count'],
                    'image' => $item['image'],
                    'color' => $item['color'],
                ];
            })->values()->all();

        return view('admin.child-mood-tracker.details', [
            'title' => 'Child Mood Tracker Summary',
            'user_id' => $user_id,
            'calendarData' => $allDates,
            'moodRing' => $moodRing,
            'topEmotions' => $topEmotions,
            'month' => $month,
            'year' => $year,
        ]);
    }



    // public function storeLikedVideoContent(Request $request){
    //     $language = $request->language ?? 'english';
    //     $user_id = auth()->id();
    //     $video_id = $request->video_id;
    //     $type = $request->type;

    //     if (!$video_id || !in_array($type, ['like', 'dislike', 'favourite'])) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'english' ? 'Invalid request.' : '无效的请求。',
    //         ], 200);
    //     }

    //     if ($type === 'favourite') {
    //         $hasDisliked = UserLikedVideo::where('user_id', $user_id)
    //             ->where('video_id', $video_id)
    //             ->where('type', 'dislike')
    //             ->exists();

    //         if ($hasDisliked) {
    //                 UserLikedVideo::where('user_id', $user_id)
    //                 ->where('video_id', $video_id)
    //                 ->where('type', 'dislike')->delete();
    //                 UserLikedVideo::create([
    //                     'user_id' => $user_id,
    //                     'video_id' => $video_id,
    //                     'type' => 'favourite',
    //                 ]);
    //                 UserLikedVideo::create([
    //                     'user_id' => $user_id,
    //                     'video_id' => $video_id,
    //                     'type' => 'like',
    //                 ]);
    //         }
    //     }elseif($type === 'dislike'){
    //         $hasFavourite = UserLikedVideo::where('user_id', $user_id)
    //             ->where('video_id', $video_id)
    //             ->where('type', 'favourite')
    //             ->exists();

    //         if ($hasFavourite) {
    //             UserLikedVideo::where('user_id', $user_id)
    //             ->where('video_id', $video_id)
    //             ->where('type', 'favourite')->delete();
    //         }
    //         $hasLiked = UserLikedVideo::where('user_id', $user_id)
    //         ->where('video_id', $video_id)
    //         ->where('type', 'like')
    //         ->exists();

    //         if($hasLiked){
    //         UserLikedVideo::where('user_id', $user_id)
    //         ->where('video_id', $video_id)
    //         ->where('type', 'like')->delete();
    //         }

    //     }elseif($type === 'like'){
    //         $hasDisliked = UserLikedVideo::where('user_id', $user_id)
    //         ->where('video_id', $video_id)
    //         ->where('type', 'dislike')
    //         ->exists();
    //         if($hasDisliked){
    //             $hasDisliked = UserLikedVideo::where('user_id', $user_id)
    //             ->where('video_id', $video_id)
    //             ->where('type', 'dislike')
    //             ->delete();
    //         }
    //     }

    //     $existing = UserLikedVideo::where('user_id', $user_id)
    //         ->where('video_id', $video_id)
    //         ->where('type', $type)
    //         ->first();

    //     if ($existing) {
    //         $existing->delete();

    //         return response()->json([
    //             'status' => true,
    //             'message' => $language == 'english'
    //                 ? ucfirst($type) . ' removed successfully!'
    //                 : ($type === 'favourite' ? '已成功移除收藏！' : '已成功移除！'),
    //         ], 200);
    //     } else {
    //         UserLikedVideo::create([
    //             'user_id' => $user_id,
    //             'video_id' => $video_id,
    //             'type' => $type,
    //         ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => $language == 'english'
    //                 ? ucfirst($type) . ' added successfully!'
    //                 : ($type === 'favourite' ? '收藏成功！' : '添加成功！'),
    //         ], 200);
    //     }
    // }

    public function storeLikedVideoContent(Request $request)
    {
        $language = $request->language ?? 'english';
        $user_id = auth()->id();
        $video_id = $request->video_id;
        $type = $request->type;

        if (!$video_id || !in_array($type, ['like', 'dislike', 'favourite'])) {
            return ApiResponse::error(
                $language === 'english' ? 'Invalid request.' : '无效的请求。',
                200
            );
        }

        // if ($type === 'favourite') {
        //     UserLikedVideo::where('user_id', $user_id)
        //         ->where('video_id', $video_id)
        //         ->whereIn('type', ['dislike'])
        //         ->delete();

        //     // Also ensure 'like' is added when 'favourite' is added
        //     if (!UserLikedVideo::where('user_id', $user_id)->where('video_id', $video_id)->where('type', 'like')->exists()) {
        //         UserLikedVideo::create([
        //             'user_id' => $user_id,
        //             'video_id' => $video_id,
        //             'type' => 'like',
        //         ]);
        //     }
        // }
        if ($type === 'favourite') {
            // Sirf purana dislike remove karega
            UserLikedVideo::where('user_id', $user_id)
                ->where('video_id', $video_id)
                ->where('type', 'dislike')
                ->delete();
        } elseif ($type === 'dislike') {
            UserLikedVideo::where('user_id', $user_id)
                ->where('video_id', $video_id)
                ->whereIn('type', ['favourite', 'like'])
                ->delete();
        } elseif ($type === 'like') {
            UserLikedVideo::where('user_id', $user_id)
                ->where('video_id', $video_id)
                ->where('type', 'dislike')
                ->delete();
        }

        $existing = UserLikedVideo::where('user_id', $user_id)
            ->where('video_id', $video_id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = $type === 'favourite' ? '已成功移除收藏！' : '已成功移除！';
            return ApiResponse::success(
                null,
                $language === 'english' ? ucfirst($type) . ' removed successfully!' : $msg
            );
        } else {
            UserLikedVideo::create([
                'user_id' => $user_id,
                'video_id' => $video_id,
                'type' => $type,
            ]);
            $msg = $type === 'favourite' ? '收藏成功！' : '添加成功！';
            return ApiResponse::success(
                null,
                $language === 'english' ? ucfirst($type) . ' added successfully!' : $msg
            );
        }
    }

    private function decodeReferredVideo(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function moodDateToString(mixed $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
