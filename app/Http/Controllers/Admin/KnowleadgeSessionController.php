<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AgeGroup;
use App\Models\ArticleSuggestion;
use App\Models\KnowledgeSession;
use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\School;
use Yajra\Datatables\Datatables;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\PermissionUser;
use App\Models\User;
use App\Models\UserArticaleLike;
use Auth;

class KnowleadgeSessionController extends Controller
{

    private $knowledgeSession = 18;
    private $subadmin_menu_id = 18;
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
        return view('admin.knowledgeSession.index', compact('pre', 'categories'));
    }


    public function articaleUsersModel(Request $request)
    {
        $videoId = $request->article_id;
        $type = $request->type;

        if (!in_array($type, ['like', 'dislike', 'favourite'])) {
            return response()->json(['users' => []]);
        }

        $users = UserArticaleLike::where('article_id', $videoId)
            ->where('type', $type)
            ->with('user:id,name,email')
            ->get()
            ->pluck('user');

        return response()->json(['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['ages'] =  AgeGroup::where('status', '1')->get();
            $data['category'] =  Category::where('status', 'active')->get();
            $data['article'] = KnowledgeSession::where(['status' => 'active'])->get();
            // $data['school'] =  School::where('status','active')->get();
            return view('admin.knowledgeSession.create', $data);
        }
        return redirect('dashboard');
    }


    public function getknowledge(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            // $ageGroup = KnowledgeSession::select('*')->orderBy('id', 'DESC')->get();

            $query = KnowledgeSession::query();
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }

            $ageGroup = $query->orderBy('id', 'DESC')->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("knowledge-session/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("delete-knowledge-session/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('title', function ($row) {
                        $plainTexttitle = strip_tags($row->title);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('description', function ($row) {
                        $plainTextDescription = strip_tags($row->description);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })

                    ->editColumn('category', function ($row) {
                        $categoryIds = is_array($row->category_id) ? $row->category_id : [$row->category_id];
                        if (!empty($categoryIds)) {
                            $categoryNames = Category::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
                            return "<span class='plan category'>" . implode(', ', $categoryNames) . "</span>";
                        }
                        return "<span class='plan category'>N/A</span>";
                    })
                    ->editColumn('session_date_time', function ($row) {
                        $sessionDate = \Carbon\Carbon::parse($row->session_date)->format('d M Y'); // Adjust format as needed
                        $sessionTime = \Carbon\Carbon::parse($row->session_time)->format('h:i A'); // Adjust format as needed
                        return "<span class='plan session_date_time'>{$sessionDate} at {$sessionTime}</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
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

                    // ->addColumn('total_likes', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'like')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="like">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_dislikes', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'dislike')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="dislike">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_favourite', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'favourite')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="favourite">' . $count . ' &#9432;</a>';
                    // })
                    ->addColumn('like_count', function ($row) {
                        $url = route("article-user-like.index", ['article_id' => $row->id]);
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


                    ->rawColumns(['action', 'like_count', 'total_likes', 'total_dislikes', 'total_favourite', 'color', 'title_color', 'title', 'description', 'category',  'status', 'session_date_time'])
                    ->make(true);
            } else {
                return Datatables::of($ageGroup)
                    ->editColumn('title', function ($row) {
                        $plainTexttitle = strip_tags($row->title);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('description', function ($row) {
                        $plainTextDescription = strip_tags($row->description);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
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
                    ->editColumn('session_date_time', function ($row) {
                        $sessionDate = \Carbon\Carbon::parse($row->session_date)->format('d M Y'); // Adjust format as needed
                        $sessionTime = \Carbon\Carbon::parse($row->session_time)->format('h:i A'); // Adjust format as needed
                        return "<span class='plan session_date_time'>{$sessionDate} at {$sessionTime}</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })

                    // ->addColumn('total_likes', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'like')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="like">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_dislikes', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'dislike')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="dislike">' . $count . ' &#9432;</a>';
                    // })
                    // ->addColumn('total_favourite', function ($row) {
                    //     $count = UserArticaleLike::where('article_id', $row->id)->where('type', 'favourite')->count();
                    //     return '<a href="javascript:void(0);" class="show-users text-primary fw-bold" style="text-decoration: none; font-weight:800;" data-id="' . $row->id . '" data-type="favourite">' . $count . ' &#9432;</a>';
                    // })
                    ->addColumn('like_count', function ($row) {
                        $url = route("article-user-like.index", ['article_id' => $row->id]);
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
                    ->rawColumns(['title', 'like_count', 'color', 'total_likes', 'total_dislikes', 'total_favourite', 'title_color', 'description', 'category',  'status', 'session_date_time'])
                    ->addIndexColumn()
                    ->make(true);
            }
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'title' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
    //         'written_by' => 'required|string|max:255|not_regex:/<[^>]*>/u',
    //         'description' => 'required|string',
    //         // 'media' => 'nullable|mimes:mp4,mov,avi,wmv', 
    //         'color' => 'nullable',
    //         'title_color' => 'nullable',
    //         'session_date' => [
    //             'nullable',
    //             'required_if:session_type,online,offline,hybrid',
    //         ],
    //         'session_time' =>  [
    //             'nullable',
    //             'required_if:session_type,online,offline,hybrid',
    //         ],
    //         'banner_image' => 'required|mimes:jpg,png,jpeg',
    //         // 'age_range' => $request->user_type === 'child' ? 'required' : 'nullable',
    //         'age_range' => 'nullable',
    //         // 'is_featured' => $request->user_type === 'child'
    //         //     ? 'nullable|in:yes,no'
    //         //     : 'nullable|in:yes,no',
    //         // 'status' => 'required|in:active,inactive',
    //         //  'title_chinese' => 'required|string|min:3|max:255',
    //         //  'description_chinese' => 'required|string',
    //         // 'user_type' => 'required|in:child,parent',
    //         'session_type' => 'required|in:online,offline,hybrid,article',
    //         'category' => 'required',
    //         'venue' => [
    //             'nullable',
    //             'required_if:session_type,offline,hybrid',
    //             'string',
    //             'min:3',
    //             'max:255',
    //             'not_regex:/<[^>]*>/u',
    //         ],

    //         'link' => [
    //             'nullable',
    //             'required_if:session_type,online,hybrid',
    //             'string',
    //             'min:3',
    //             'max:255',
    //             'url', // Validates proper URL format
    //             'not_regex:/<[^>]*>/u',
    //         ],

    //     ], [
    //         'is_featured.required' => 'The featured field is required.',
    //         // 'description_chinese.required' => 'The description field is required.',
    //         'title_chinese.required' => 'The title field is required.'
    //     ]);


    //     // $mediaPath = null;
    //     // if ($request->hasFile('media')) {
    //     //     $mediaPath = $request->file('media')->store('videos', 'public');
    //     // }
    //     $applyName = null;
    //     if ($request->hasFile('banner_image')) {
    //         $applyName = time() .  $request->file('banner_image')->getClientOriginalName();
    //         $request->file('banner_image')->move(public_path('assets/images'), $applyName);
    //     }
    //     // Translate title and description to Chinese
    //     // $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
    //     // $titleChinese = $translator->translate($request->title);
    //     // $descriptionChinese = $translator->translate(strip_tags($request->description));

    //     $article =   KnowledgeSession::create([
    //         'category_id' => $request->category,
    //         'session_type' => $request->session_type,
    //         'link' => $request->link,
    //         'banner_image' => $applyName,
    //         'venue' => $request->venue,
    //         'title' => $request->title,
    //         'description' => $request->description,
    //         'age' => $request->age_range,
    //         'session_date'  => $request->session_date ?? now()->toDateString(),
    //         'session_time'  => $request->session_time ?? now()->toTimeString(),
    //         'status' => 'active',
    //         'is_featured' =>  'yes',
    //         'color' => $request->color,
    //         'title_color' => $request->title_color,
    //         // 'title_chinese' => $request->title_chinese,
    //         // 'description_chinese' => $request->description_chinese,
    //         'user_type' => 'parent',
    //         'written_by'=>$request->written_by
    //     ]);


    //     /** 🔔 Send Notification to all users of given type */

    //     $userIds = User::where([
    //         'user_type' => 'parent',
    //         'status' => 'active',
    //         'is_notification' => 'true'
    //     ])
    //         ->pluck('id')
    //         ->toArray();

    //     $users = DeviceToken::whereIn('user_id', $userIds)
    //         ->whereNotNull('token')
    //         ->get();

    //     // Get content from notification template
    //     $content = getNotificationContent('add_article', [
    //         'title' => $article->title,
    //     ]);

    //     $notification_type = 'add_article';
    //     $user_type = "user";

    //     $userData = [
    //         'title'      => $article->title,
    //         'id'       => $article->id,
    //         'color' => $article->color,
    //         'title_color' => $article->title_color,
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

    //     return redirect()->route('knowledgeSession.index')->with('success', 'Knowledge session content added successfully.');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'written_by' => 'required|string|max:255|not_regex:/<[^>]*>/u',
            'description' => 'required|string',
            'banner_image' => 'required|mimes:jpg,png,jpeg',
            'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'session_type' => 'required|in:online,offline,hybrid,article',
            'category' => 'required|array|min:1',
            'category.*' => 'exists:category,id', // ✅ सही table का नाम (पहले गलत था: category)
            'session_date' => 'nullable|required_if:session_type,online,offline,hybrid',
            'session_time' => 'nullable|required_if:session_type,online,offline,hybrid',
            'venue' => [
                'nullable',
                'required_if:session_type,offline,hybrid',
                'string',
                'min:3',
                'max:255',
                'not_regex:/<[^>]*>/u',
            ],
            'link' => [
                'nullable',
                'required_if:session_type,online,hybrid',
                'string',
                'min:3',
                'max:255',
                'url',
                'not_regex:/<[^>]*>/u',
            ],

            'article_ids' => 'nullable|array',
            'article_ids.*' => 'exists:knowledge_sessions,id',
        ]);

        // $applyName = null;
        // if ($request->hasFile('banner_image')) {
        //     $applyName = time() .  $request->file('banner_image')->getClientOriginalName();
        //     $request->file('banner_image')->move(public_path('assets/images'), $applyName);
        // }
        $applyName = null;
        if ($request->banner_image) {
            $applyName = uploadFile($request->file('banner_image'), 'assets/images');
            if (!$applyName) {
                return back()->with('error', 'Failed to upload banner image.');
            }
        }

        $createdArticles = [];
        $selectedArticleIds = $request->input('article_ids', []);

        foreach ($request->category as $categoryId) {
            // ✅ Category details fetch करें ताकि हर category की color और title_color अलग-अलग आए
            $category = Category::find($categoryId);

            if (!$category) {
                continue; // अगर category नहीं मिली तो skip कर दें
            }

            $article = KnowledgeSession::create([
                'category_id' => $categoryId,
                'session_type' => $request->session_type,
                'link' => $request->link,
                'banner_image' => $applyName,
                'venue' => $request->venue,
                'title' => $request->title,
                'description' => $request->description,
                'age' => $request->age_range,
                'session_date'  => $request->session_date ?? now()->toDateString(),
                'session_time'  => $request->session_time ?? now()->toTimeString(),
                'status' => 'active',
                'is_featured' => 'yes',
                // ✅ Category से color values dynamically ले रहे हैं
                'color' => $category->color ?? null,
                'title_color' => $category->title_color ?? null,
                'user_type' => 'parent',
                'written_by' => $request->written_by,
                'featured_key' => $request->featured_key,
            ]);

            // Save article suggestions in separate table
            if (!empty($selectedArticleIds)) {
                foreach ($selectedArticleIds as $selectedId) {
                    ArticleSuggestion::create([
                        'knowledge_session_id' => $article->id,
                        'article_ids' => $selectedId, // har article ko alag row me save
                    ]);
                }
            }
            $createdArticles[] = $article;
        }
        // Notifications
        foreach ($createdArticles as $article) {
            $userIds = User::where([
                'user_type' => 'parent',
                'status' => 'active',
                'is_notification' => 'true'
            ])->pluck('id')->toArray();

            $users = DeviceToken::whereIn('user_id', $userIds)
                ->whereNotNull('token')
                ->get();

            $content = getNotificationContent('add_article', [
                'title' => $article->title,
            ]);

            $userData = [
                'title'      => $article->title,
                'id'         => $article->id,
                'color'      => $article->color,
                'title_color' => $article->title_color,
                'type'       => 'add_article',
            ];

            foreach ($users as $user) {
                sendNotificationSender(
                    $user->user_id,
                    $content['title'],
                    $content['body'],
                    'add_article',
                    $userData,
                    "user"
                );
            }
        }

        return redirect()->route('knowledgeSession.index')
            ->with('success', 'Knowledge sessions created successfully for selected categories.');
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
            $data = KnowledgeSession::with('articleSuggestions')->findOrFail($id);

            $ages = AgeGroup::where('status', '1')->get()->toArray();
            $categories = Category::where('status', 'active')->get();


            $selectedArticleIds = $data->articleSuggestions->pluck('article_ids')->toArray();


            $allArticles = KnowledgeSession::where('session_type', 'article')
                ->where('id', '!=', $id)
                ->get();

            return view('admin.knowledgeSession.edit', compact('data', 'ages', 'categories', 'selectedArticleIds', 'allArticles'));
        }
        return redirect('dashboard');
    }




    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //    dd($request->all());
        // dd($request->description);
        $request->validate([
            'title' => 'required|string|max:255|not_regex:/<[^>]*>/u',
            'written_by' => 'required|string|max:255|not_regex:/<[^>]*>/u',
            'description' => 'required|string',
            'session_type' => 'required|in:online,offline,hybrid,article',
            // 'link' => 'required',
            // 'venue' => 'required',
            'color' => 'nullable',
            'title_color' => 'nullable',
            // 'age_range' => $request->user_type === 'child' ? 'required' : 'nullable',
            'age_range' => 'nullable',
            'featured_key' => 'nullable|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'session_date' => [
                'nullable',
                'required_if:session_type,online,offline,hybrid',
            ],
            'session_time' =>  [
                'nullable',
                'required_if:session_type,online,offline,hybrid',
            ],
            'banner_image' => 'nullable|mimes:jpg,jpeg,png',
            // 'is_featured' => $request->user_type === 'child'
            //     ? 'nullable|in:yes,no'
            //     : 'nullable|in:yes,no',
            'status' => 'required|in:active,inactive',
            // 'user_type' => 'required|in:child,parent',
            'category' => 'required',
            'venue' => [
                'nullable',
                'required_if:session_type,offline,hybrid',
                'string',
                'min:3',
                'max:255',
                'not_regex:/<[^>]*>/u',
            ],
            'link' => [
                'nullable',
                'required_if:session_type,online,hybrid',
                'string',
                'min:3',
                'max:255',
                'url', // Validates proper URL format
                'not_regex:/<[^>]*>/u',
            ],
            'article_ids' => 'nullable|array',
            'article_ids.*' => 'exists:knowledge_sessions,id',
        ], [
            'is_featured.required' => 'The featured field is required.'
        ]);

        $knowledgeSession = KnowledgeSession::findOrFail($id);
        // dd($knowledgeSession);
        // Handle media upload
        // if ($request->hasFile('media')) {
        //     // Delete old media if exists
        //     if (!empty($knowledgeSession->media) && file_exists(public_path('uploads/videos/' . $knowledgeSession->media))) {
        //         unlink(public_path('uploads/videos/' . $knowledgeSession->media));
        //     }

        //     // Store new media
        //     $file = $request->file('media');
        //     $filename = time() . '.' . $file->getClientOriginalExtension();
        //     $file->move(public_path('uploads/videos'), $filename);
        //     $knowledgeSession->media = $filename;
        // }
        if ($request->hasFile('banner_image')) {
            if (!empty($knowledgeSession->banner_image)) {
                uploadFile(null, 'assets/images', $knowledgeSession->banner_image);
            }
            $image = $request->file('banner_image');
            $newFileName = uploadFile($image, 'assets/images', $knowledgeSession->banner_image);
            // DB update
            $knowledgeSession->banner_image = $newFileName;
        }


        // Translate title and description to Chinese
        // $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        // $titleChinese = $translator->translate($request->title);
        // $descriptionChinese = $translator->translate(strip_tags($request->description));


        // Update other fields
        $knowledgeSession->title = $request->title;
        $knowledgeSession->description = $request->description;
        $knowledgeSession->session_type = $request->session_type;
        $knowledgeSession->category_id = $request->category;
        $knowledgeSession->link = $request->link;
        $knowledgeSession->venue = $request->venue;
        $knowledgeSession->age = $request->age_range;
        $knowledgeSession->session_date = $request->session_date ?? now()->toDateString();
        $knowledgeSession->session_time =  $request->session_time ?? now()->toTimeString();
        $knowledgeSession->status = $request->status;
        $knowledgeSession->is_featured = 'yes';
        // $knowledgeSession->title_chinese = $request->title_chinese;
        // $knowledgeSession->description_chinese = $request->description_chinese;
        $knowledgeSession->user_type = 'parent';
        $knowledgeSession->color = $request->color;
        $knowledgeSession->title_color = $request->title_color;
        $knowledgeSession->written_by = $request->written_by;
        $knowledgeSession->featured_key = $request->featured_key;

        // 🔹 Article Suggestions Update
        ArticleSuggestion::where('knowledge_session_id', $knowledgeSession->id)->delete();
        if (!empty($request->article_ids)) {
            foreach ($request->article_ids as $selectedId) {
                ArticleSuggestion::create([
                    'knowledge_session_id' => $knowledgeSession->id,
                    'article_ids' => $selectedId,
                ]);
            }
        }


        $knowledgeSession->save();
        return redirect()->route('knowledgeSession.index')->with('success', 'Knowledge session content updated successfully.');
    }



    /**
     * Remove the specified resource from storage.
     */
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
