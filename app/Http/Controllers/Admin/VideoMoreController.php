<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
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
class VideoMoreController extends Controller
{
      private $video_content = 17;
    private $subadmin_menu_id = 38;
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $categories = Category::select('id', 'category_name')
                    ->where('status', 'active')
                    ->orderBy('category_name')
                    ->get();
        $videoContents = VideoContent::where('mood', 'mood')->get();
        return view('admin.video-other.index', compact('pre', 'categories', 'videoContents'));
    }

    public function create()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $data['ages'] =  AgeGroup::where('status', '1')->get();
        $data['category'] =  Category::where('status', 'active')->get();
        $data['mood'] = 'mood';
        return view('admin.video-other.create', $data);
    }

    public function store(Request $request)
    {
    $request->validate([
        'title' => 'required|string|min:3|max:100|not_regex:/<[^>]*>/u',
        'description' => 'nullable|string',
        'category' => 'required|string|max:255',
        'media' => 'nullable|mimes:mp4,mov,avi,wmv',
        'thumbnail' => 'nullable',
        'points' => 'nullable|numeric|min:0|max:10',
        'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
        'is_featured' => 'required|in:yes,no',
        'ratio_type' => 'required|in:landscape,portrait',
        'written_by' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
        'color' => 'required',
        'age_range' => 'nullable',
        'title_color' => 'required',
    ], [
        'is_featured.required' => 'The featured field is required.',
    ]);

    $mediaPath = null;
    $videoDuration = "00:00";
    $thumbnailPath = null;

    if ($request->hasFile('thumbnail')) {
        $thumbnailFile = $request->file('thumbnail');
        $thumbnailPath = uploadFile($thumbnailFile, 'assets/images');
    }

    if ($request->hasFile('media')) {
    $file = $request->file('media');

    // 1. File name ko sanitize karein (Spaces hatayein)
    $originalName = $file->getClientOriginalName();
    $safeName = time() . '_' . str_replace(' ', '_', $originalName);

    // 2. Temp path ke liye safe name use karein (Move karne se pehle copy karein)
    $tmpVideoPath = sys_get_temp_dir() . '/' . $safeName;
    copy($file->getRealPath(), $tmpVideoPath);

    // 3. Ab file ko uske final destination par move karein
    $file->move(public_path('assets/video'), $safeName);
    $mediaName = $safeName;
    $mediaPath = $mediaName;

    if (file_exists($tmpVideoPath)) {
        try {
            // FFProbe logic
            $ffprobe = FFProbe::create();
            $durationInSeconds = $ffprobe->format($tmpVideoPath)->get('duration');

            if ($durationInSeconds !== null) {
                $minutes = floor($durationInSeconds / 60);
                $seconds = intval($durationInSeconds % 60);
                $videoDuration = sprintf("%02d:%02d", $minutes, $seconds);
            }

            if (!$thumbnailPath) {
                $ffmpeg = FFMpeg::create();
                $video = $ffmpeg->open($tmpVideoPath);

                $thumbnailName = pathinfo($mediaName, PATHINFO_FILENAME) . '.jpg';
                $tmpThumbnailPath = sys_get_temp_dir() . '/' . $thumbnailName;

                $video->frame(TimeCode::fromSeconds(1))->save($tmpThumbnailPath);

                if (file_exists($tmpThumbnailPath)) {
                    // Yahan check karein ki uploadFile helper function safeName accept karta hai ya nahi
                    $thumbnailFile = new \Illuminate\Http\UploadedFile($tmpThumbnailPath, $thumbnailName, 'image/jpeg', null, true);
                    $thumbnailPath = uploadFile($thumbnailFile, 'assets/images');
                }
                @unlink($tmpThumbnailPath);
            }
        } catch (\Exception $e) {
            \Log::error("FFMpeg Error: " . $e->getMessage());
        }

        // Temp file delete karna mat bhulein
        if (file_exists($tmpVideoPath)) {
            @unlink($tmpVideoPath);
        }
    }
}

    $video_content_details = VideoContent::create([
        'title' => $request->title,
        'description' => $request->description,
        'age' => $request->age_range ?? '11-18',
        'category_id' => $request->category,
        'video_link' => $mediaPath,
        'video_duration' => $videoDuration,
        'written_by' => $request->written_by,
        'points' => $request->points ?? 0,
        'featured_key' => $request->featured_key,
        'ratio_type' => $request->ratio_type,
        'color' => $request->color,
        'title_color' => $request->title_color,
        'is_featured' => $request->is_featured,
        'thumbnail' => $thumbnailPath,
        'mood' => 'mood',
    ]);

    $userQuery = User::where('status', 'active')->where('is_notification', 'true');

    if ($request->has('school_id') && !empty($request->school_id)) {
        $userQuery->whereIn('school_id', $request->school_id);
    }

    $userIds = $userQuery->pluck('id')->toArray();
    $users = DeviceToken::whereIn('user_id', $userIds)
        ->whereNotNull('token')
        ->get();

    $content = getNotificationContent('video_content', [
        'title' => $video_content_details->title,
    ]);

    $userData = [
        'video_id'    => $video_content_details->id,
        'title'       => $video_content_details->title,
        'thumbnail'   => asset('assets/images/' . $video_content_details->thumbnail),
        'video_link'  => asset('assets/video/' . $video_content_details->video_link),
        'id'          => $video_content_details->category_id,
        'color'       => $video_content_details->color,
        'title_color' => $video_content_details->title_color,
        'type'        => 'video_content',
    ];

    foreach ($users as $user) {
        sendNotificationSender(
            $user->user_id,
            $content['title'],
            $content['body'],
            'video_content',
            $userData,
            "user"
        );
    }

    return redirect()->route('video-other.index')->with('success', 'Video Content added successfully.');
    }

    public function getVideoData(Request $request)
  {
    $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    if (!isset($pre) || empty($pre)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    if ($request->ajax()) {
$query = VideoContent::where('mood', 'mood')->orderBy('id', 'DESC');
        // Category Filter
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        $videos = $query->get();

        return DataTables::of($videos)
    ->addIndexColumn()
    ->addColumn('category', function ($row) {
        $categoryNames = Category::where('id', $row->category_id)->pluck('category_name')->toArray();
        return "<span class='plan category'>" . implode(', ', $categoryNames) . "</span>";
    })
    // ->addColumn('mood', function ($row) {
    //     return '<span class="badge badge-primary" style="background-color: #3B82F6; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold;">' . strtoupper($row->mood ?? 'HAPPY') . '</span>';
    // })
    ->addColumn('color', function ($row) {
        return '<div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 25px; height: 25px; background-color: ' . $row->color . '; border: 1px solid #ccc; border-radius:4px;"></div>
                    <span>' . $row->color . '</span>
                </div>';
    })
    ->addColumn('title_color', function ($row) {
        return '<div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 25px; height: 25px; background-color: ' . $row->title_color . '; border: 1px solid #ccc; border-radius:4px;"></div>
                    <span>' . $row->title_color . '</span>
                </div>';
    })
    ->editColumn('status', function ($row) {
        return '<span class="badge" style="background-color: #D1FAE5; color: #059669; padding: 5px 15px; border-radius: 20px;">' . ucfirst($row->status) . '</span>';
    })
    ->addColumn('action', function ($row) use ($pre) {
    if ($pre->is_modify == 'yes') {
        return '
            <div style="display: flex; align-items: center; justify-content: flex-start; gap: 10px; min-width: 80px; padding-left: 75px;">
                <a href="' . route("video-other.edit", $row->id) . '"
                   title="Edit"
                   style="color: #4B5563; font-size: 20px; text-decoration: none; display: inline-flex;">
                    <i class="mdi mdi-pencil"></i>
                </a>

                <a href="javascript:void(0)"
                   class="delete"
                   data-id="' . $row->id . '"
                   title="Delete"
                   style="color: #EF4444; font-size: 20px; text-decoration: none; display: inline-flex;">
                    <i class="mdi mdi-trash-can"></i>
                </a>
            </div>';
    }
    return '<div style="text-align:center;">-</div>';
   })
    ->rawColumns(['action','color', 'title_color', 'status', 'category'])
    ->make(true);
    }
  }

     public function edit(string $id)
{
    $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    if (!empty($pre) && $pre->is_modify == 'yes') {
        $data = VideoContent::find($id);

        if (!$data) {
            return redirect()->route('video-other.index')->with('error', 'Data not found');
        }

        $ages = AgeGroup::where('status', '1')->get();
        $categories = Category::where('status', 'active')->get();

        return view('admin.video-other.edit', compact('data', 'ages', 'categories',));
    }
    return redirect('dashboard');
}

// public function update(Request $request, string $id)
// {
//     $request->validate([
//         'title' => 'required|string|max:255',
//         'written_by' => 'required|string|max:255',
//         'description' => 'required|string',
//         'category' => 'required',
//         'status' => 'required|in:active,inactive',
//         'ratio_type' => 'required|in:landscape,portrait',
//         'media' => 'nullable|mimes:mp4,mov,ogg,qt',
//         'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:5000',
//     ]);

//     $videoContent = VideoContent::findOrFail($id);

//     if ($request->hasFile('media')) {
//         $videoContent->video_link = uploadFile($request->file('media'), 'assets/video', $videoContent->video_link);
//     }

//     if ($request->hasFile('thumbnail')) {
//         $videoContent->thumbnail = uploadFile($request->file('thumbnail'), 'assets/images', $videoContent->thumbnail);
//     }

//     $videoContent->title = $request->title;
//     $videoContent->description = $request->description;
//     $videoContent->category_id = $request->category;
//     $videoContent->status = $request->status;
//     $videoContent->ratio_type = $request->ratio_type;
//     $videoContent->is_featured = $request->is_featured ?? 'no';
//     $videoContent->featured_key = $request->featured_key;
//     $videoContent->written_by = $request->written_by;
//     $videoContent->mood = 'mood';
//     $videoContent->color = $request->color;
//     $videoContent->title_color = $request->title_color;



//     $videoContent->save();

//     if ($request->ajax()) {
//         return response()->json([
//             'success' => true,
//             'message' => 'Updated successfully.',
//             'redirect'=> route('video-other.index')
//         ]);
//     }

//     return redirect()->route('video-other.index')->with('success', 'Updated successfully.');
// }

public function update(Request $request, string $id)
    {
        // Purely matching your original live-validations
        $request->validate([
            'title' => 'required|string|max:255',
            'written_by' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required',
            'status' => 'required|in:active,inactive',
            'ratio_type' => 'required|in:landscape,portrait',
            'media' => 'nullable|mimes:mp4,mov,ogg,qt,avi,wmv', // Synchronized with store types safely
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:5000',
        ]);

        $videoContent = VideoContent::findOrFail($id);

        if ($request->hasFile('media')) {
            $file = $request->file('media');

            $originalName = $file->getClientOriginalName();
            $safeName = time() . '_' . str_replace(' ', '_', $originalName);
            $tmpVideoPath = sys_get_temp_dir() . '/' . $safeName;
            copy($file->getRealPath(), $tmpVideoPath);

            $videoContent->video_link = uploadFile($file, 'assets/video', $videoContent->video_link);

            if (file_exists($tmpVideoPath)) {
                try {
                    $ffprobe = FFProbe::create();
                    $durationInSeconds = $ffprobe->format($tmpVideoPath)->get('duration');
                    if ($durationInSeconds !== null) {
                        $minutes = floor($durationInSeconds / 60);
                        $seconds = intval($durationInSeconds % 60);
                        $videoContent->video_duration = sprintf("%02d:%02d", $minutes, $seconds);
                    }
                } catch (\Exception $e) {
                    \Log::error("FFMpeg Probe Update Error: " . $e->getMessage());
                }
                @unlink($tmpVideoPath);
            }
        }

        if ($request->hasFile('thumbnail')) {
            $videoContent->thumbnail = uploadFile($request->file('thumbnail'), 'assets/images', $videoContent->thumbnail);
        }

        // Maintaining exact database scheme & request mapping to prevent breaks
        $videoContent->title = $request->title;
        $videoContent->description = $request->description;
        $videoContent->category_id = $request->category;
        $videoContent->status = $request->status;
        $videoContent->ratio_type = $request->ratio_type;
        $videoContent->is_featured = $request->is_featured ?? 'no';
        $videoContent->featured_key = $request->featured_key;
        $videoContent->written_by = $request->written_by;
        $videoContent->mood = 'mood';
        $videoContent->color = $request->color;
        $videoContent->title_color = $request->title_color;

        $videoContent->save();

        // Exact response layout match for your custom sweetalerts/AJAX workflows
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Updated successfully.',
                'redirect'=> route('video-other.index')
            ]);
        }

        return redirect()->route('video-other.index')->with('success', 'Updated successfully.');
    }

 public function destroy(string $id)
    {
        $knowledgeSession = KnowledgeSession::find($id);

        if ($knowledgeSession) {
            $knowledgeSession->status = 'inactive';
            $knowledgeSession->save();

            $knowledgeSession->delete();
        }

        return redirect()->back()->with('success', 'Knowledge session content has been marked as inactive and deleted.');
    }

}
