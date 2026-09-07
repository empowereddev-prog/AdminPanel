<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\QuizCategory;
use Illuminate\Http\Request;
use Yajra\Datatables\datatables;
use Validator;
use App\Models\UserAttemptQuiz;
use App\Models\QuizQuestion;
use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Models\PermissionUser;
use Auth;
use App\Models\VideoContent;
use Illuminate\Support\Facades\DB;

class QuizCategoryController extends Controller
{

    private $quiz_category = 20;
    private $subadmin_menu_id = 19;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz_category);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.quizCategory.index', compact('pre'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz_category);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $video_title = VideoContent::where('status', 'active')->get();
        $colors = Color::latest()->get();
        return view('admin.quizCategory.create', compact('video_title', 'colors'));
    }

    public function getCategory(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz_category);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            // $ageGroup = QuizCategory::select('*')->orderBy('id', 'DESC')->get();
            // $ageGroup = QuizCategory::orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->get();

            $ageGroup = QuizCategory::withCount('quiz')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        // $btn .= '<a href="' . route("quiz-questions.index") . '" title="Add Question" style="margin-left:5px;font-size:20px"><i class="mdi mdi-plus"></i></a>&nbsp;';
                        $btn .= '<a href="' . route("quiz.index", ['id' => $row->id]) . '" class="edit btn btn-info btn-sm">Add Quiz</a> ';

                        $btn .= '<a href="' . url("quiz-category/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        // $btn .= '<a href="' . url("quiz-delete-category/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';                    return $btn;
                        return $btn;
                    })

                    ->editColumn('category_name', function ($row) {
                        return "<span class='plan $row->category_name'>" . ucfirst($row->category_name) . "</span>";
                    })
                    ->addColumn('question_count', function ($row) {
                        $url = route("quiz.index", ['id' => $row->id]);
                        return '
                            <a href="' . $url . '"
                               class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
                               style="min-width:100px; padding:5px 10px; font-size:12px; font-weight:500;"
                               title="View Quizzes">

                                <span> Quiz </span>&nbsp;
                                <span class="badge bg-danger text-light"
                                      style="font-size:11px; min-width:20px; height:20px; display:flex; align-items:center; justify-content:center;">
                                    ' . $row->quiz_count . '
                                </span>
                            </a>
                        ';
                    })

                    ->addColumn('priority', function ($row) {
                        return '<input type="text" name="priority"
                                    class="form-control priority-input"
                                    data-id="' . $row->id . '"
                                    value="' . ($row->priority ?? 0) . '"
                                    placeholder="Enter Priority"
                                    min="1" style="width: 60px;">';
                    })

                    ->editColumn('banner_image', function ($row) {
                        $img = '<img src="' . asset('assets/images/' . $row['banner_image']) . '" alt="No Banner Image" width="80" height="80">';
                        return $img;
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
                    ->rawColumns(['action', 'question_count','color','title_color','banner_image', 'category_name',  'status', 'priority'])
                    ->make(true);
            } else {
                return DataTables::of($ageGroup)
                    ->addIndexColumn()
                    ->editColumn('category_name', function ($row) {
                        return "<span class='plan $row->category_name'>" . ucfirst($row->category_name) . "</span>";
                    })
                    ->editColumn('banner_image', function ($row) {
                        $img = '<img src="' . asset('assets/images/' . $row['banner_image']) . '" alt="No Banner Image" width="80" height="80">';
                        return $img;
                    })
                    ->addColumn('question_count', function ($row) {
                        $url = route("quiz.index", ['id' => $row->id]);
                        return '
                            <a href="' . $url . '"
                               class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2"
                               style="min-width:100px; padding:5px 10px; font-size:12px; font-weight:500;"
                               title="View Quizzes">

                                <span> Quiz </span>&nbsp;
                                <span class="badge bg-danger text-light"
                                      style="font-size:11px; min-width:20px; height:20px; display:flex; align-items:center; justify-content:center;">
                                    ' . $row->quiz_count . '
                                </span>
                            </a>
                        ';
                    })
                    ->addColumn('priority', function ($row) {
                        return '<input type="text" name="priority"
                                    class="form-control priority-input"
                                    data-id="' . $row->id . '"
                                    value="' . ($row->priority ?? 0) . '"
                                    placeholder="Enter Priority"
                                    min="1" style="width: 60px;">';
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

                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['banner_image','question_count','color','title_color', 'category_name',  'status', 'priority'])
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
            'category_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'not_regex:/<[^>]*>/u',
                \Illuminate\Validation\Rule::unique('quiz_categories')->whereNull('deleted_at')
            ],
            // 'category_name_chinese' => ['required',
            //                     'string',
            //                     'min:3','max:255',
            //                     \Illuminate\Validation\Rule::unique('quiz_categories')->whereNull('deleted_at')
            //                     ],
            'description' => 'required|string|min:10',
            'color' => 'required',
            'title_color' => 'required',
            // 'description_chinese' =>'required|string',
            'video' => 'nullable|array',
            'video.*' => 'exists:video_contents,id',
            'banner_image' => 'required|mimes:jpeg,png,jpg',
        ]);
        $applyName = null;
        if ($request->hasFile('banner_image')) {
            $applyName = time() .  $request->file('banner_image')->getClientOriginalName();
            $request->file('banner_image')->move(public_path('assets/images'), $applyName);
        }
        // Translate title and description to Chinese
        // $descriptionChinese = $translator->translate(strip_tags($request->description));
        // dd($applyName);

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
                $title_chinese = $video->title; // default fallback
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

        QuizCategory::create([
            'color' => $request->color,
            'title_color' => $request->title_color,
            'category_name' => $request->category_name,
            // 'category_name_chinese' => $request->category_name_chinese,
            'description' => $request->description,
            // 'description_chinese' => $request->description_chinese,
            'banner_image' => $applyName,
            'referred_video' => json_encode($referredVideos)
        ]);


        return redirect()->route('quizCategory.index')->with('success', 'Category created successfully.');
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
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz_category);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = QuizCategory::where('id', $id)->first();
            $referred_video = VideoContent::where('status', 'active')->get();
            $data->referred_video = json_decode($data->referred_video, true);
            $colors = Color::latest()->get();
            return view('admin.quizCategory.edit', compact('data', 'referred_video', 'colors'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'category_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'not_regex:/<[^>]*>/u',
                \Illuminate\Validation\Rule::unique('quiz_categories')
                    ->ignore($id)->whereNull('deleted_at')
            ],
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg', // Ensure it's an image
            // 'category_name_chinese' => ['required',
            //                     'string',
            //                     'min:3','max:255',
            //                     \Illuminate\Validation\Rule::unique('quiz_categories')
            //                     ->ignore($id)->whereNull('deleted_at')],
            'description' => 'required|string|min:10',
            // 'description_chinese' =>'required|string',
            'video' => 'nullable|array',
            'video.*' => 'exists:video_contents,id',
            'status' => 'required|in:active,inactive',
            'color' => 'required',
            'title_color' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $category = QuizCategory::findOrFail($id);

        if ($request->hasFile('banner_image')) {
            $image = $request->file('banner_image');

            // Ensure the directory exists & has proper permissions
            $destinationPath = public_path('assets/images/');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Delete old image if exists
            if (!empty($category->banner_image) && file_exists($destinationPath . $category->banner_image)) {
                unlink($destinationPath . $category->banner_image);
            }

            // Generate a unique name and move the file
            $newFileName = time() . '_' . $image->getClientOriginalName();
            $image->move($destinationPath, $newFileName);
            $category->banner_image = $newFileName;
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

        // $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        // $categoryChinese = $translator->translate($request->category_name);
        $category->update([
            'status' => $request->status,
            'category_name' => $request->category_name,
            // 'category_name_chinese' => $request->category_name_chinese,
            'description' => $request->description,
            // 'description_chinese' => $request->description_chinese,
            'color' => $request->color,
            'title_color' => $request->title_color,
            'referred_video' => json_encode($referredVideos),
        ]);

        return redirect()->route('quizCategory.index')->with('success', 'Category updated successfully');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = QuizCategory::find($id);

        if ($category) {
            $category->status = 'inactive';
            $category->save();
            QuizQuestion::where('quiz_category_id', $category->id)->delete();
            $category->delete();
        }

        return redirect()->back()->with('success', 'Category has been marked as inactive and soft deleted.');
    }

    public function categories()
    {
        $ageGroup = QuizCategory::select('*')->get();

        // Add image path to each category
        foreach ($ageGroup as $category) {
            $category->banner_image = asset('assets/images/' . $category->imageName);
        }


        return response()->json([
            'data' => $ageGroup,
            'message' => 'Categories Retrieved Successfully.',
            'status' => true,
        ]);
    }


    public function updatePriority(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:quiz_categories,id',
            'priority' => 'required|integer|min:1',
        ]);

        try {
            $id = $request->id;
            $newPriority = $request->priority;

            // Optional: Reset other categories with the same priority
            // QuizCategory::where('priority', $newPriority)
            //     ->where('id', '!=', $id)
            //     ->update(['priority' => 0]);

            // Update selected category
            $quizCategory = QuizCategory::findOrFail($id);
            $quizCategory->priority = $newPriority;
            $quizCategory->save();

            return response()->json([
                'message' => 'Priority updated successfully.',
                'priority' => $quizCategory->priority
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating priority: ' . $e->getMessage()
            ], 500);
        }
    }
}
