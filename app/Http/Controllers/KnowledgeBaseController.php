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

class KnowledgeBaseController extends Controller
{


    private $video_content = 17;
    private $subadmin_menu_id = 17;
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
        return view('admin.knowledge-base.index', compact('pre', 'categories'));
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
        return view('admin.knowledge-base.create', $data);
    }


    public function getknowledge(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $query = VideoContent::with('category')->orderBy('id', 'DESC');
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
            // $ageGroup = $query->where('user_type', 'parent')->get();
            $ageGroup = $query->whereIn('user_type', ['parent', 'both'])->get();

            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    // ->addColumn('action', function ($row) {
                    //     $btn = "";
                    //     $btn .= '<a href="' . url("knowledge-base/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    //     $btn .= '<a href="' . url("delete-knowledge-base/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    //     return $btn;
                    // })
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



                            <a href="' . url("knowledge-base/" . $row->id . "/edit") . '"
                            title="Edit"
                            style="font-size:18px; padding:4px 6px;margin-left:6px;">
                                <i class="mdi mdi-pencil"></i>
                            </a>

                            <a href="' . url("delete-knowledge-base/" . $row->id) . '"
                            class="delete"
                            title="Delete"
                            data-id="' . $row->id . '"
                            style="font-size:18px; padding:4px 6px;">
                                <i class="mdi mdi-trash-can"></i>
                            </a>

                        </div>
                    ';
                    })


                    // ->addColumn('like_count', function ($row) {
                    //     $url = route("user-like.index", ['video_id' => $row->id]);
                    //     return '
                    //         <a href="' . $url . '"
                    //            class="btn btn-outline-success d-flex align-items-center justify-content-center gap-2"
                    //            style="min-width:120px; padding:5px 10px; font-size:12px; font-weight:500;"
                    //            title="View Users Who Liked">
                    //             <i class="mdi mdi-thumb-up-outline"> </i>
                    //             <span>&nbsp; Likes</span>
                    //         </a>
                    //     ';
                    // })

                    ->addColumn('like_count', function ($row) {

                        $userType = ucfirst($row->user_type); // parent / child / both

                        return '<span class="badge text-dark" style="font-size:12px;">
                                    ' . $userType . '
                                </span>';
                    })

                    // ->addColumn('total_likes', function ($row) {

                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'like')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="like">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_dislikes', function ($row) {
                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'dislike')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="dislike">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_favourite', function ($row) {
                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'favourite')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="favourite">' . $count . ' &#9432;</a>';
                    // })
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
                    // ->addColumn('total_likes', function ($row) {
                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'like')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="like">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_dislikes', function ($row) {
                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'dislike')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="dislike">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_favourite', function ($row) {
                    //     $count = UserLikedVideo::where('video_id', $row->id)->where('type', 'favourite')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="favourite">' . $count . ' &#9432;</a>';
                    // })
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
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
            'description' => 'nullable|string|min:10',
            'category' => 'required|string|max:255',
            'school_id' => 'nullable|array',
            'school_id.*' => 'integer|exists:schools,id',
            'media' => 'nullable|mimes:mp4,mov,avi,wmv',
            'thumbnail' => 'nullable',
            'points' => 'nullable|numeric|min:0|max:10',
            'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'is_featured' => 'required|in:yes,no',
            'ratio_type' => 'required|in:landscape,portrait',
            'written_by' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'color' => 'required',
            'age_range' => [
                'nullable',
                // 'required_if:user_type,child',
            ],
            'title_color' => 'required',
            //  'title_chinese' => 'required|string|min:3|max:255',
            //  'description_chinese' => 'required|string',
            'user_type' => 'required|in:child,parent,both'
        ], [
            'is_featured.required' => 'The featured field is required.',
            //  'description_chinese.required' => 'The description field is required.',
            //  'title_chinese.required' => 'The title field is required.'
        ]);

        $mediaPath = null;
        $videoDuration = "00:00";
        $thumbnailPath = null;

        /** Thumbnail Handling */
        if ($request->hasFile('thumbnail')) {
            $thumbnailFile = $request->file('thumbnail');
            $thumbnailName = uploadFile($thumbnailFile, 'assets/images'); // S3 upload
            $thumbnailPath = $thumbnailName; // DB me sirf filename
        }

        /** Media (Video) Handling */
        elseif ($request->hasFile('media')) {
            $file = $request->file('media');

            // Direct video ko S3 me upload karo
            $mediaName = uploadFile($file, 'assets/video');
            $mediaPath = $mediaName;

            // Local copy banani zaroori hai FFMpeg ke liye
            $tmpVideoPath = sys_get_temp_dir() . '/' . time() . '_' . $file->getClientOriginalName();
            copy($file->getRealPath(), $tmpVideoPath);

            if (file_exists($tmpVideoPath)) {
                // Video ka duration nikalna
                $ffprobe = FFProbe::create();
                $durationInSeconds = $ffprobe->format($tmpVideoPath)->get('duration');

                if ($durationInSeconds !== null) {
                    $minutes = floor($durationInSeconds / 60);
                    $seconds = intval($durationInSeconds % 60);
                    $videoDuration = sprintf("%02d:%02d", $minutes, $seconds);
                }

                // Thumbnail generate karna
                $ffmpeg = FFMpeg::create();
                $video = $ffmpeg->open($tmpVideoPath);
                $thumbnailName = pathinfo($mediaName, PATHINFO_FILENAME) . '.jpg';
                $tmpThumbnailPath = sys_get_temp_dir() . '/' . $thumbnailName;

                $video->frame(TimeCode::fromSeconds(1))->save($tmpThumbnailPath);

                if (file_exists($tmpThumbnailPath)) {
                    // Thumbnail ko S3 pe upload karo
                    $thumbnailFile = new \Illuminate\Http\UploadedFile(
                        $tmpThumbnailPath,
                        $thumbnailName,
                        'image/jpeg',
                        null,
                        true
                    );
                    $thumbnailName = uploadFile($thumbnailFile, 'assets/images');
                    $thumbnailPath = $thumbnailName;
                }

                // Local temp files delete
                @unlink($tmpVideoPath);
                @unlink($tmpThumbnailPath);
            }
        }

        $video_content_details = VideoContent::create([
            'title' => $request->title,
            'description' => $request->description,
            'age' => $request->age_range ?? '11-18',
            'category_id' => $request->category,
            'school_id' => ($request->has('school_id') && is_array($request->school_id)) 
                    ? array_values(array_map('intval', array_filter($request->school_id, 'is_numeric'))) 
                    : null,
            'video_link' => $mediaPath,
            'last_watched_duration' => $request->last_watched_duration,
            'video_duration' => $videoDuration,
            'written_by' => $request->written_by,
            'points' => $request->points ?? 0,
            'featured_key' => $request->featured_key,
            'ratio_type' => $request->ratio_type,
            'color' => $request->color,
            'title_color' => $request->title_color,
            // 'title_chinese' => $request->title_chinese,
            // 'description_chinese' => $request->description_chinese,
            'is_featured' => $request->is_featured,
            'user_type' => $request->user_type,
            'thumbnail' => $thumbnailPath ?? null // Store thumbnail path

        ]);

        /** 🔔 Send Notification to all users of given type */
        if ($request->user_type == 'child') {
            $userIds = User::where(['user_type' => 'child', 'status' => 'active'])
                ->pluck('id')
                ->toArray();
        } elseif ($request->user_type == 'parent') {
            $userIds = User::where(['user_type' => 'parent', 'status' => 'active', 'is_notification' => 'true'])
                ->pluck('id')
                ->toArray();
        } elseif ($request->user_type == 'both') {
            $userIds = User::where(['status' => 'active', 'is_notification' => 'true'])
                ->pluck('id')
                ->toArray();
        }
        $users = DeviceToken::whereIn('user_id', $userIds)
            ->whereNotNull('token')
            ->get();

        // Get content from notification template
        $content = getNotificationContent('video_content', [
            'title'      => $video_content_details->title,
            // 'video_link' => asset('assets/video/' . $video_content_details->video_link),
            // 'thumbnail'  => $video_content_details->thumbnail,

        ]);

        $notification_type = 'video_content';
        $user_type = "user";

        $userData = [
            'video_id'   => $video_content_details->id,
            'title'      => $video_content_details->title,
            'thumbnail'  => asset('assets/images/' . $video_content_details->thumbnail),
            'video_link' => asset('assets/video/' . $video_content_details->video_link),
            'id'         => $video_content_details->category_id,
            'color'      => $video_content_details->color,
            'title_color' => $video_content_details->title_color,
            'type'       => $notification_type,
        ];


        foreach ($users as $user) {
            sendNotificationSender(
                $user->user_id,
                $content['title'],   // template se subject
                $content['body'],    // template se description
                $notification_type,
                $userData,
                $user_type
            );
        }


        return redirect()->route('knowledge-base.index')->with('success', 'Video Content added successfully.');
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

            return view('admin.knowledge-base.edit', compact('data', 'ages', 'categories', 'schools'));
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
        // if ($request->hasFile('media')) {
        //     if (!empty($knowledgeBase->media) && public_path('assets/video/' . $knowledgeBase->video_link)) {
        //         unlink(public_path($knowledgeBase->media));
        //     }
        //     $file = $request->file('media');
        //     $filename = $file->getClientOriginalName(); // Unique filename
        //     $destinationPath = public_path('assets/video/');
        //     if (!file_exists($destinationPath)) {
        //         mkdir($destinationPath, 0777, true);
        //     }
        //     $file->move($destinationPath, $filename);
        //     $knowledgeBase->video_link = $filename;
        //     $videoPath = public_path('assets/video/' . $filename); // Correct file path
        //     if (file_exists($videoPath)) {
        //         $ffprobe = FFProbe::create();
        //         $durationInSeconds = $ffprobe->format($videoPath)->get('duration');
        //         $knowledgeBase->video_duration = $durationInSeconds
        //             ? sprintf("%02d:%02d", floor($durationInSeconds / 60), intval($durationInSeconds % 60))
        //             : "00:00";
        //         $ffmpeg = FFMpeg::create();
        //         $video = $ffmpeg->open($videoPath);
        //         $thumbnailName = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        //         $thumbnailPath = public_path('assets/images/' . $thumbnailName);
        //         if (!file_exists(public_path('assets/images/'))) {
        //             mkdir(public_path('assets/images/'), 0777, true);
        //         }
        //         $video->frame(TimeCode::fromSeconds(1))->save($thumbnailPath);
        //         $knowledgeBase->thumbnail = $thumbnailName;
        //     }
        // }
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
        $userIds = User::where('user_type', $request->user_type)->pluck('id')->toArray();

        $title = 'Progress Looks Good on You!';
        $message = 'New video uploaded: ' . $knowledgeBase->title;
        $userData = [
            'video_id' => $knowledgeBase->id,
            'title' => $knowledgeBase->title,
            'thumbnail' => $knowledgeBase->thumbnail,
            'video_link' => asset('assets/video/' . $knowledgeBase->video_link)
        ];
        $type = 'video_content';

        sendNotificationToUsers($userIds, $title, $message, $userData, $type);

        return redirect()->route('knowledge-base.index')->with('success', 'Video Content updated successfully.');
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
    // public function showUsers($type, $videoId) {
    //     $validTypes = ['likes', 'dislikes', 'favourites'];
    //     if (!in_array($type, $validTypes)) {
    //         abort(404);
    //     }

    //     $users = UserLikedVideo::where('video_id', $videoId)
    //                 ->where('type', rtrim($type, 's'))
    //                 ->with('user')
    //                 ->get();

    //     return view('admin.videos.user_list', compact('users', 'type'));
    // }

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
