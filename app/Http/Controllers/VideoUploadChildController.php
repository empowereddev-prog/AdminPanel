<?php

namespace App\Http\Controllers;


use App\Models\AgeGroup;
use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\KnowledgeBase;
use App\Models\School;
use App\Models\VideoContent;
use Illuminate\Http\Request;
use Yajra\Datatables\datatables;
use Validator;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\PermissionUser;
use Auth;
use FFMpeg\Coordinate\TimeCode;
use App\Models\User;
use App\Models\UserLikedVideo;

class VideoUploadChildController extends Controller
{

    private $video_content = 17;
    private $subadmin_menu_id = 38;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $categories = Category::select('id', 'category_name')->orderBy('category_name')->get();
        return view('admin.knowledge-base-child.index', compact('pre', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $data['ages'] =  AgeGroup::where('status', '1')->get();
        $data['category'] =  Category::where('status', 'active')->get();
        $data['school'] =  School::where('status', 'active')->get();
        return view('admin.knowledge-base-child.create', $data);
    }


    public function getknowledge(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {


            // $query = VideoContent::with('category')->orderBy('id', 'DESC');

            //Quick Fix
            $query = VideoContent::with('category')
                ->where(function($q) {
                    $q->whereNull('mood')
                    ->orWhere('mood', '!=', 'mood');
                })
                ->orderBy('id', 'DESC');



            // 🟢 User Type filter (same pattern as category)
            // if ($request->has('user_type') && !empty($request->user_type)) {
            //     $query->where('user_type', $request->user_type);
            // }


            if ($request->has('category_id') && !empty($request->category_id)) {
                $query->where(function ($q) use ($request) {
                    $q->where('category_id', $request->category_id)
                        ->orWhereRaw('FIND_IN_SET(?, category_id)', [$request->category_id]);
                });
            }
            $ageGroup = $query->whereIn('user_type', ['child', 'both'])->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()

                    ->addColumn('action', function ($row) {

                        $likeUrl = route("user-like.index", ['video_id' => $row->id]);
                        $likeCount = UserLikedVideo::where('video_id', $row->id)
                            ->where('type', 'like')
                            ->count();

                        return '
                        <div class="d-flex align-items-center gap-2" style="white-space: nowrap;">

                           <a href="' . $likeUrl . '"
                                class="position-relative d-inline-flex align-items-center justify-content-center"
                                style="
                                        min-width:48px;
                                        height:30px;
                                        border:1px solid #1A5EDB;
                                        border-radius:6px;
                                        color:#1A5EDB;
                                        text-decoration:none;
                                "
                                title="View Users Who Liked">

                                    <i class="mdi mdi-thumb-up-outline" style="font-size:16px;"></i>

                                    <!-- Cart-style count badge -->
                                    <span
                                        class="position-absolute"
                                        style="
                                            top:-6px;
                                            right:-6px;
                                            background:#1A5EDB;
                                            color:#fff;
                                            width:16px;
                                            height:16px;
                                            border-radius:50%;
                                            font-size:9px;
                                            line-height:16px;
                                            text-align:center;
                                            font-weight:600;
                                        ">
                                        ' . $likeCount . '
                                    </span>
                                </a>



                            <a href="' . url("knowledge-base-child/" . $row->id . "/edit") . '"
                            title="Edit"
                            style="font-size:18px; padding:4px 6px;margin-left:6px;">
                                <i class="mdi mdi-pencil"></i>
                            </a>

                            <a href="' . url("delete-knowledge-base-child/" . $row->id) . '"
                            class="delete"
                            title="Delete"
                            data-id="' . $row->id . '"
                            style="font-size:18px; padding:4px 6px;">
                                <i class="mdi mdi-trash-can"></i>
                            </a>

                        </div>
                    ';
                    })



                    ->addColumn('like_count', function ($row) {

                        $userType = ucfirst($row->user_type); // parent / child / both

                        return '<span class="badge text-dark" style="font-size:12px;">
                                    ' . $userType . '
                                </span>';
                    })


                    ->editColumn('title', function ($row) {
                        $plainTexttitle = strip_tags($row->title);
                        $truncatedtitle = substr($plainTexttitle, 0, 25);

                        if (strlen($plainTexttitle) > 25) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('description', function ($row) {
                        $plainTextDescription = strip_tags($row->description);
                        $truncatedDescription = substr($plainTextDescription, 0, 25);

                        if (strlen($plainTextDescription) > 25) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })
                    ->addColumn('color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->color . '</span>
                        </div>';
                    })
                    ->addColumn('title_color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->title_color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->title_color . '</span>
                        </div>';
                    })
                    ->editColumn('category', function ($row) {
                        $categoryIds = is_array($row->category_id) ? $row->category_id : [$row->category_id];
                        if (!empty($categoryIds)) {
                            $categoryNames = Category::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
                            return "<span class='plan category'>" . implode(', ', $categoryNames) . "</span>";
                        }
                        return "<span class='plan category'>N/A</span>";
                    })


                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['action', 'like_count', 'color', 'title_color', 'category', 'title', 'description', 'age_range',  'status', 'total_likes', 'total_dislikes', 'total_favourite'])
                    ->make(true);
            } else {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->editColumn('title', function ($row) {
                        $plainTexttitle = strip_tags($row->title);
                        $truncatedtitle = substr($plainTexttitle, 0, 25);

                        if (strlen($plainTexttitle) > 25) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->addColumn('like_count', function ($row) {
                        $url = route("user-like.index", ['video_id' => $row->id]);
                        return '
                            <a href="' . $url . '"
                               class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2"
                               style="min-width:120px; padding:5px 10px; font-size:12px; font-weight:500;"
                               title="View Users Who Liked">
                                <i class="mdi mdi-thumb-up-outline"> </i>
                                <span>&nbsp; Likes</span>
                            </a>
                        ';
                    })

                    ->editColumn('description', function ($row) {
                        $plainTextDescription = strip_tags($row->description);
                        $truncatedDescription = substr($plainTextDescription, 0, 25);

                        if (strlen($plainTextDescription) > 25) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })
                    ->addColumn('color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->color . '</span>
                        </div>';
                    })
                    ->addColumn('title_color', function ($row) {
                        return '
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="width: 30px; height: 30px; background-color: ' . $row->title_color . '; border: 1px solid #ccc;"></div>
                            <span>' . $row->title_color . '</span>
                        </div>';
                    })
                    ->editColumn('category', function ($row) {
                        $categoryIds = is_array($row->category_id) ? $row->category_id : [$row->category_id];
                        if (!empty($categoryIds)) {
                            $categoryNames = Category::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
                            return "<span class='plan category'>" . implode(', ', $categoryNames) . "</span>";
                        }
                        return "<span class='plan category'>N/A</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['category', 'like_count', 'color', 'title_color', 'title', 'description', 'age_range',  'status', 'total_likes', 'total_dislikes', 'total_favourite'])
                    ->make(true);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
    //         'description' => 'nullable|string|min:10',
    //         'category' => 'required|string|max:255',
    //         'school_id' => 'nullable|array',
    //         'school_id.*' => 'integer|exists:schools,id',
    //         'media' => 'nullable|mimes:mp4,mov,avi,wmv',
    //         'thumbnail' => 'nullable',
    //         'points' => 'nullable|numeric|min:0|max:10',
    //         'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
    //         'is_featured' => 'required|in:yes,no',
    //         'ratio_type' => 'required|in:landscape,portrait',
    //         'written_by' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
    //         'color' => 'required',
    //         'age_range' => [
    //             'nullable',
    //             // 'required_if:user_type,child',
    //         ],
    //         'title_color' => 'required',
    //         //  'title_chinese' => 'required|string|min:3|max:255',
    //         //  'description_chinese' => 'required|string',
    //         'user_type' => 'required|in:child,parent,both'
    //     ], [
    //         'is_featured.required' => 'The featured field is required.',
    //         //  'description_chinese.required' => 'The description field is required.',
    //         //  'title_chinese.required' => 'The title field is required.'
    //     ]);

    //     $mediaPath = null;
    //     $videoDuration = "00:00";
    //     $thumbnailPath = null;

    //     if ($request->hasFile('thumbnail')) {
    //         $thumbnailFile = $request->file('thumbnail');
    //         $thumbnailPath = uploadFile($thumbnailFile, 'assets/images');
    //     }

    //    if ($request->hasFile('media')) {
    //     $file = $request->file('media');
    //     $mediaName = uploadFile($file, 'assets/video');
    //     $mediaPath = $mediaName;

    //         // Local copy banani zaroori hai FFMpeg ke liye
    //         $tmpVideoPath = sys_get_temp_dir() . '/' . time() . '_' . $file->getClientOriginalName();
    //         copy($file->getRealPath(), $tmpVideoPath);

    //         if (file_exists($tmpVideoPath)) {
    //             // Video ka duration nikalna
    //             $ffprobe = FFProbe::create();
    //             $durationInSeconds = $ffprobe->format($tmpVideoPath)->get('duration');

    //             if ($durationInSeconds !== null) {
    //                 $minutes = floor($durationInSeconds / 60);
    //                 $seconds = intval($durationInSeconds % 60);
    //                 $videoDuration = sprintf("%02d:%02d", $minutes, $seconds);
    //             }
    //             if (!$thumbnailPath) {
    //                 $ffmpeg = FFMpeg::create();
    //                 $video = $ffmpeg->open($tmpVideoPath);
    //                 $thumbnailName = pathinfo($mediaName, PATHINFO_FILENAME) . '.jpg';
    //                 $tmpThumbnailPath = sys_get_temp_dir() . '/' . $thumbnailName;

    //             $video->frame(TimeCode::fromSeconds(1))->save($tmpThumbnailPath);

    //             if (file_exists($tmpThumbnailPath)) {
    //                 $thumbnailFile = new \Illuminate\Http\UploadedFile($tmpThumbnailPath, $thumbnailName, 'image/jpeg', null, true);
    //                 $thumbnailPath = uploadFile($thumbnailFile, 'assets/images');
    //             }
    //                 @unlink($tmpThumbnailPath);
    //             }
    //             @unlink($tmpVideoPath);
    //         }
    //     }

    //     $video_content_details = VideoContent::create([
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'age' => $request->age_range ?? '11-18',
    //         'category_id' => $request->category,
    //         'school_id' => ($request->has('school_id') && is_array($request->school_id))
    //                 ? array_values(array_map('intval', array_filter($request->school_id, 'is_numeric')))
    //                 : null,
    //         'video_link' => $mediaPath,
    //         'last_watched_duration' => $request->last_watched_duration,
    //         'video_duration' => $videoDuration,
    //         'written_by' => $request->written_by,
    //         'points' => $request->points ?? 0,
    //         'featured_key' => $request->featured_key,
    //         'ratio_type' => $request->ratio_type,
    //         'color' => $request->color,
    //         'title_color' => $request->title_color,
    //         // 'title_chinese' => $request->title_chinese,
    //         // 'description_chinese' => $request->description_chinese,
    //         'is_featured' => $request->is_featured,
    //         'user_type' => $request->user_type,
    //         'thumbnail' => $thumbnailPath

    //     ]);
    //     $userQuery = User::where('status', 'active')->where('is_notification', 'true');

    //     if ($request->user_type != 'both') {
    //         $userQuery->where('user_type', $request->user_type);
    //     }

    //     if ($request->has('school_id') && !empty($request->school_id)) {
    //         $userQuery->whereIn('school_id', $request->school_id);
    //     }

    //     $userIds = $userQuery->pluck('id')->toArray();
    //     $users = DeviceToken::whereIn('user_id', $userIds)
    //         ->whereNotNull('token')
    //         ->get();

    //     // Get content from notification template
    //     $content = getNotificationContent('video_content', [
    //         'title'      => $video_content_details->title,
    //         // 'video_link' => asset('assets/video/' . $video_content_details->video_link),
    //         // 'thumbnail'  => $video_content_details->thumbnail,

    //     ]);

    //     $notification_type = 'video_content';
    //     $user_type = "user";

    //     $userData = [
    //         'video_id'   => $video_content_details->id,
    //         'title'      => $video_content_details->title,
    //         'thumbnail'  => asset('assets/images/' . $video_content_details->thumbnail),
    //         'video_link' => asset('assets/video/' . $video_content_details->video_link),
    //         'id'         => $video_content_details->category_id,
    //         'color'      => $video_content_details->color,
    //         'title_color' => $video_content_details->title_color,
    //         'type'       => $notification_type,
    //     ];


    //     foreach ($users as $user) {
    //         sendNotificationSender(
    //             $user->user_id,
    //             $content['title'],   // template se subject
    //             $content['body'],    // template se description
    //             $notification_type,
    //             $userData,
    //             $user_type
    //         );
    //     }


    //     return redirect()->route('knowledge-base-child.index')->with('success', 'Video Content added successfully.');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
            'description' => 'nullable|string|min:10',
            'category' => 'required|string|max:255',
            'school_id' => 'nullable|array',
            'school_id.*' => 'integer|exists:schools,id',
            'media' => 'nullable|mimes:mp4,mov,avi,wmv|max:5242880',
            'thumbnail' => 'nullable',
            'points' => 'nullable|numeric|min:0|max:10',
            'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'is_featured' => 'required|in:yes,no',
            'ratio_type' => 'required|in:landscape,portrait',
            'written_by' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'color' => 'required',
            'age_range' => ['nullable'],
            'title_color' => 'required',
            'user_type' => 'required|in:child,parent,both'
        ], [
            'is_featured.required' => 'The featured field is required.',
        ]);

        $mediaPath = null;
        $videoDuration = "00:00";
        $thumbnailPath = null;

        /**
         * Thumbnail Upload
         */
        if ($request->hasFile('thumbnail')) {

            $thumbnailFile = $request->file('thumbnail');

            $thumbnailPath = uploadFile($thumbnailFile, 'assets/images');
        }

        /**
         * Video Upload + Processing
         */
        if ($request->hasFile('media')) {

            try {

                $file = $request->file('media');

                /**
                 * Upload original video
                 */
                $mediaName = uploadFile($file, 'assets/video');

                $mediaPath = $mediaName;

                /**
                 * Use uploaded temp file
                 */
                // $tmpVideoPath = $file->getRealPath();

                $tmpVideoPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $file->getClientOriginalExtension();
                file_put_contents($tmpVideoPath, file_get_contents($file));

                if ($tmpVideoPath && file_exists($tmpVideoPath)) {

                    /**
                     * Get duration
                     */
                    $ffprobe = FFProbe::create();

                    $durationInSeconds = $ffprobe
                        ->format($tmpVideoPath)
                        ->get('duration');

                    if ($durationInSeconds !== null) {

                        $minutes = floor($durationInSeconds / 60);

                        $seconds = intval($durationInSeconds % 60);

                        $videoDuration = sprintf(
                            "%02d:%02d",
                            $minutes,
                            $seconds
                        );
                    }

                    /**
                     * Auto thumbnail generate
                     */
                    if (!$thumbnailPath) {

                        $ffmpeg = FFMpeg::create();

                        $video = $ffmpeg->open($tmpVideoPath);

                        $thumbnailName =
                            pathinfo($mediaName, PATHINFO_FILENAME) . '.jpg';

                        $tmpThumbnailPath =
                            sys_get_temp_dir() . '/' . $thumbnailName;

                        $video
                            ->frame(TimeCode::fromSeconds(1))
                            ->save($tmpThumbnailPath);

                        if (file_exists($tmpThumbnailPath)) {

                            $thumbnailFile =
                                new \Illuminate\Http\UploadedFile(
                                    $tmpThumbnailPath,
                                    $thumbnailName,
                                    'image/jpeg',
                                    null,
                                    true
                                );

                            $thumbnailPath =
                                uploadFile($thumbnailFile, 'assets/images');

                            @unlink($tmpThumbnailPath);
                        }
                    }
                }

            } catch (\Exception $e) {

                \Log::error('Video Processing Error: ' . $e->getMessage());

                return back()
                    ->withInput()
                    ->with('error', 'Video upload failed: ' . $e->getMessage());
            }
        }

        /**
         * Save DB
         */
        $video_content_details = VideoContent::create([

            'title' => $request->title,
            'description' => $request->description,
            'age' => $request->age_range ?? '11-18',
            'category_id' => $request->category,

            'school_id' => (
                $request->has('school_id') &&
                is_array($request->school_id)
            )
                ? array_values(
                    array_map(
                        'intval',
                        array_filter(
                            $request->school_id,
                            'is_numeric'
                        )
                    )
                )
                : null,

            'video_link' => $mediaPath,

            'last_watched_duration' =>
                $request->last_watched_duration,

            'video_duration' => $videoDuration,

            'written_by' => $request->written_by,

            'points' => $request->points ?? 0,

            'featured_key' => $request->featured_key,

            'ratio_type' => $request->ratio_type,

            'color' => $request->color,

            'title_color' => $request->title_color,

            'is_featured' => $request->is_featured,

            'user_type' => $request->user_type,

            'thumbnail' => $thumbnailPath

        ]);

        /**
         * Notifications
         */
        $userQuery = User::where('status', 'active')
            ->where('is_notification', 'true');

        if ($request->user_type != 'both') {

            $userQuery->where(
                'user_type',
                $request->user_type
            );
        }

        if (
            $request->has('school_id') &&
            !empty($request->school_id)
        ) {

            $userQuery->whereIn(
                'school_id',
                $request->school_id
            );
        }

        $userIds = $userQuery->pluck('id')->toArray();

        $users = DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->get();

        $content = getNotificationContent('video_content', [
            'title' => $video_content_details->title,
        ]);

        $notification_type = 'video_content';

        $user_type = "user";

        $userData = [

            'video_id' => $video_content_details->id,

            'title' => $video_content_details->title,

            'thumbnail' => asset(
                'assets/images/' .
                $video_content_details->thumbnail
            ),

            'video_link' => asset(
                'assets/video/' .
                $video_content_details->video_link
            ),

            'id' => $video_content_details->category_id,

            'color' => $video_content_details->color,

            'title_color' =>
                $video_content_details->title_color,

            'type' => $notification_type,
        ];

        foreach ($users as $user) {

            sendNotificationSender(

                $user->user_id,

                $content['title'],

                $content['body'],

                $notification_type,

                $userData,

                $user_type
            );
        }

        return redirect()
            ->route('knowledge-base-child.index')
            ->with(
                'success',
                'Video Content added successfully.'
            );
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
            $data = VideoContent::where('id', $id)->first();
            // Ensure JSON fields are decoded properly

            $ages = AgeGroup::where('status', '1')->get()->toArray();
            $categories = Category::where('status', 'active')->get();
            $schools = School::where('status', 'active')->get()->toArray();

            return view('admin.knowledge-base-child.edit', compact('data', 'ages', 'categories', 'schools'));
        }
        return redirect('dashboard');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:100|not_regex:/<[^>]*>/u',
            'description' => 'nullable|string|min:10',
            'age_range' => [
                'nullable',
                // 'required_if:user_type,child',
            ],
            'category' => 'required|string|max:255',
            'school_id' => 'nullable|array',
            'school_id.*' => 'exists:schools,id',
            'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'written_by' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'media' => 'nullable',
            'thumbnail' => 'nullable',
            'color' => 'required',
            'title_color' => 'required',
            'points' => 'nullable|numeric|min:0|max:10',
            'is_featured' => 'required|in:yes,no',
            'ratio_type' => 'required|in:landscape,portrait',
            'status' => 'required|in:active,inactive',
            // 'title_chinese' => 'required|string|min:3|max:255',
            // 'description_chinese' => 'required|string',
            'user_type' => 'required|in:child,parent,both'
        ], [
            'is_featured.required' => 'The featured field is required.',
            // 'description_chinese.required' => 'The description field is required.',
            // 'title_chinese.required' => 'The title field is required.'
        ]);
        $knowledgeBase = VideoContent::findOrFail($id);

        if ($request->hasFile('thumbnail')) {
            // Purana thumbnail delete karo (agar DB me hai)
            if (!empty($knowledgeBase->thumbnail)) {
                uploadFile(null, 'assets/images', $knowledgeBase->thumbnail); // ye helper old file delete karega
            }

            $thumbnailFile = $request->file('thumbnail');
            $thumbnailName = uploadFile($thumbnailFile, 'assets/images', $knowledgeBase->thumbnail);
            $knowledgeBase->thumbnail = $thumbnailName;
        }

        /**  Media handling */
        if ($request->hasFile('media')) {
            // Purana video delete karo (agar DB me hai)
            if (!empty($knowledgeBase->video_link)) {
                uploadFile(null, 'assets/video', $knowledgeBase->video_link);
            }

            $file = $request->file('media');
            $mediaName = uploadFile($file, 'assets/video');
            $knowledgeBase->video_link = $mediaName;

            try {
            // Local copy banani zaroori hai FFMpeg ke liye
            $tmpVideoPath = sys_get_temp_dir() . '/' . time() . '_' . $file->getClientOriginalName();
            copy($file->getRealPath(), $tmpVideoPath);

            if (file_exists($tmpVideoPath)) {
                $ffprobe = FFProbe::create();
                $durationInSeconds = $ffprobe->format($tmpVideoPath)->get('duration');
                $knowledgeBase->video_duration = $durationInSeconds
                    ? sprintf("%02d:%02d", floor($durationInSeconds / 60), intval($durationInSeconds % 60))
                    : "00:00";

                /** Agar request me thumbnail nahi tha to video se thumbnail generate karo */
                if (!$request->hasFile('thumbnail')) {
                    $ffmpeg = FFMpeg::create();
                    $video = $ffmpeg->open($tmpVideoPath);

                    $thumbnailName = pathinfo($mediaName, PATHINFO_FILENAME) . '.jpg';
                    $tmpThumbnailPath = sys_get_temp_dir() . '/' . $thumbnailName;

                    $video->frame(TimeCode::fromSeconds(1))->save($tmpThumbnailPath);

                    if (file_exists($tmpThumbnailPath)) {
                        $thumbnailFile = new \Illuminate\Http\UploadedFile(
                            $tmpThumbnailPath,
                            $thumbnailName,
                            'image/jpeg',
                            null,
                            true
                        );
                        $thumbnailName = uploadFile($thumbnailFile, 'assets/images', $knowledgeBase->thumbnail);
                        $knowledgeBase->thumbnail = $thumbnailName;
                    }

                    @unlink($tmpThumbnailPath);
                }

                @unlink($tmpVideoPath);
            }
        }catch (\Exception $e) {
        }
    }
        // Update other fields
        $knowledgeBase->title = $request->title;
        $knowledgeBase->description = $request->description;
        $knowledgeBase->age = $request->age_range ?? '11-18';
        $knowledgeBase->category_id = $request->category;
        $knowledgeBase->points = $request->points;
        $knowledgeBase->status = $request->status;
        $knowledgeBase->is_featured = $request->is_featured;
        $knowledgeBase->title_chinese = $request->title_chinese;
        // $knowledgeBase->description_chinese = $request->description_chinese;
        $knowledgeBase->user_type = $request->user_type;
        $knowledgeBase->color = $request->color;
        $knowledgeBase->title_color = $request->title_color;
        $knowledgeBase->written_by = $request->written_by;
        $knowledgeBase->featured_key = $request->featured_key;
        $knowledgeBase->ratio_type = $request->ratio_type;

        if ($request->has('school_id') && is_array($request->school_id)) {
            $ids = array_filter($request->school_id, 'is_numeric');
            $knowledgeBase->school_id = array_map('intval', $ids);
        } else {
            $knowledgeBase->school_id = null;
        }

        $knowledgeBase->save();
        $userQuery = User::where('status', 'active');
        if ($request->user_type != 'both') {
            $userQuery->where('user_type', $request->user_type);
        }
        if ($request->has('school_id') && !empty($request->school_id)) {
            $userQuery->whereIn('school_id', $request->school_id);
        }

        $userIds = $userQuery->pluck('id')->toArray();

        $title = 'Progress Looks Good on You!';
        $message = 'New video uploaded: ' . $knowledgeBase->title;
        $userData = [
            'video_id' => $knowledgeBase->id,
            'title' => $knowledgeBase->title,
            'thumbnail' => $knowledgeBase->thumbnail,
            'video_link' => asset('assets/video/' . $knowledgeBase->video_link)
        ];
        $type = 'video_content';

        if (!empty($userIds)) {
            sendNotificationToUsers($userIds, $title, $message, $userData, $type);
        }

        return redirect()->route('knowledge-base-child.index')->with('success', 'Video Content updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $knowledgeBase = VideoContent::find($id);

        if ($knowledgeBase) {
            $knowledgeBase->status = 'inactive';
            $knowledgeBase->save();

            $knowledgeBase->delete();
        }

        return redirect()->back()->with('success', 'Video Content has been marked as inactive and deleted.');
    }


    public function videoUsersPopup(Request $request)
    {
        $videoId = $request->video_id;
        $type = $request->type;

        if (!in_array($type, ['like', 'dislike', 'favourite'])) {
            return response()->json(['users' => []]);
        }

        $users = UserLikedVideo::where('video_id', $videoId)
            ->where('type', $type)
            ->with('user:id,name,email')
            ->get()
            ->pluck('user');

        return response()->json(['users' => $users]);
    }
}
