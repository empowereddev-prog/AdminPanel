<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\Quiz;
use App\Models\QuizCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NewQuizController extends Controller
{
    private $quiz = 20;
    public function index($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!$pre) {
            return redirect('dashboard');
        }

        $category = QuizCategory::findOrFail($id);
        return view('admin.quiz.index', compact('pre', 'id', 'category'));
    }

    public function questionList(Request $request, $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!$pre) {
            return redirect('dashboard');
        }

        if ($request->ajax()) {
            $questions = Quiz::withCount('questions')->where('quiz_category_id', $id)->latest()->get();

            return DataTables::of($questions)
                ->addIndexColumn()
                // ->addColumn('action', function ($row) use ($id) {
                //     return '
                //         <div class="btn-group">
                //             <a href="' . route('quiz.create', ['id' => $id]) . '?edit=' . $row->id . '" 
                //                class=" " title="Edit Quiz">
                //                 <span class="material-icons">edit</span>
                //             </a>
                //             <button type="button" class="  delete" 
                //                     data-id="' . $row->id . '" title="Delete Quiz">
                //                 <span class="material-icons">delete</span>
                //             </button>
                //         </div>
                //     ';
                // })
                ->addColumn('action', function ($row) {
                    $btn  = '<a href="' . route("quiz-questions.index", ['id' => $row->id, 'category_id' => $row->quiz_category_id]) . '" class="btn btn-outline-info btn-sm" title="Add Question">Add Question</a> ';
                    $btn .= '<a href="' . route("quiz.edit", $row->id) . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil"></i></a> ';
                    $btn .= '<a href="javascript:void(0)" class="delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>';
                    return $btn;
                })

                ->addColumn('question_count', function ($row) {
                    $url = route("quiz-questions.index", ['id' => $row->id, 'category_id' => $row->quiz_category_id]);
                    return '
                        <a href="' . $url . '" 
                           class="btn btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2" 
                           style="min-width:110px; padding:5px 10px; font-size:12px; font-weight:500;"
                           title=" ' . $row->questions_count . ' Questions">
                            <span>Questions</span>
                            &nbsp;
                            <span class="badge bg-danger text-light" 
                                  style="font-size:11px;  min-width:20px; height:20px; display:flex; align-items:center; justify-content:center;">
                                ' . $row->questions_count . '
                            </span>
                        </a>
                    ';
                })


                ->editColumn('image', function ($row) {
                    $imgPath = $row->image
                        ? getImagePathUrl($row->image, 'assets/images')
                        : asset('assets/images/unnamed.jpg');
                    return '<img src="' . $imgPath . '" width="40" height="40" class="border rounded">';
                })
                ->editColumn('title', function ($row) {
                    $truncated = str()->limit(strip_tags($row->title), 50, '...');
                    return e($truncated);
                })
                ->editColumn('status', fn($row) => "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>")
                ->rawColumns(['image', 'status', 'action', 'question_count'])
                ->make(true);
        }
    }

    public function create($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!empty($pre) && $pre->is_modify === 'yes') {
            $category = QuizCategory::where(['id' => $id, 'status' => 'active'])->firstOrFail();
            return view('admin.quiz.create', [
                'title' => 'Add Question',
                'category' => $category,
                'id' => $id,
                'categoryName' => $category->category_name
            ]);
        }

        return redirect('home');
    }

    public function store(Request $request, $id)
    {
        // Validation stays the same
        $rules = [
            'category' => 'required|exists:quiz_categories,id',
            'title' => 'required|string|min:3|max:255|not_regex:/<[^>]*>/u',
            'age' => 'nullable',
            'image' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $quizCategory = QuizCategory::findOrFail($id);

        // $imageName = null;
        // if ($request->hasFile('image')) {
        //     $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
        //     $request->file('image')->move(public_path('assets/images'), $imageName);
        // }
        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = uploadFile($request->file('image'), 'assets/images');
        }
        Quiz::create([
            'quiz_category_id' => $id,
            'title' => $request->title,
            'color' => $quizCategory->color,
            'image' => $imageName,
            'age' => $request->age,
            'title_color' => $quizCategory->title_color,
            'status' => 'inactive'
        ]);
        return redirect()->route('quiz.index', ['id' => $id])
            ->with('added', 'Quiz Added Successfully!');
    }

    public function edit($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!$pre || $pre->is_modify !== 'yes') {
            return redirect('dashboard');
        }

        $quiz = Quiz::findOrFail($id);
        $category = QuizCategory::where(['id' => $quiz->quiz_category_id, 'status' => 'active'])->firstOrFail();
        $categoryName = $category->category_name;
        return view('admin.quiz.edit', compact('quiz', 'category', 'categoryName'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'title' => 'required|string|min:3|max:500|not_regex:/<[^>]*>/u',
            'status' => 'required|in:active,inactive',
            'age' => 'nullable',
            'image' => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $quiz = Quiz::findOrFail($id);
        $quizCategory = QuizCategory::findOrFail($quiz->quiz_category_id);

        $imageName = $quiz->image;

        // Handle image upload
        // if ($request->hasFile('image')) {
        //     if ($imageName && file_exists(public_path('assets/images/' . $imageName))) {
        //         unlink(public_path('assets/images/' . $imageName)); // delete old image
        //     }
        //     $imageName = time() . '_' . $request->file('image')->getClientOriginalName();
        //     $request->file('image')->move(public_path('assets/images'), $imageName);
        // }
        if ($request->hasFile('image')) {
            $imageName = uploadFile($request->file('image'), 'assets/images', $oldImage ?? null);

            if (!$imageName) {
                return response()->json(['status' => false, 'message' => 'Image upload failed.'], 500);
            }
        }

        $quiz->update([
            'title' => $request->title,
            'status' => $request->status,
            'age' => $request->age,
            'image' => $imageName,
            'color' => $quizCategory->color,
            'title_color' => $quizCategory->title_color
        ]);

        return redirect()->route('quiz.index', ['id' => $quiz->quiz_category_id])
            ->with('success', 'Quiz updated successfully!');
    }

    public function delete($id)
    {
        $quiz = Quiz::findOrFail($id);
        // Remove image if exists
        if ($quiz->image && Storage::disk('s3')->exists('assets/images/' . $quiz->image)) {
            Storage::disk('s3')->delete('assets/images/' . $quiz->image);
        }
        $quiz->delete();
        return response()->json(['success' => true, 'message' => 'Quiz deleted successfully.']);
    }
}
