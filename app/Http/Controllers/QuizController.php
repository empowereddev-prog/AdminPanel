<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use App\Models\Category;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use Yajra\Datatables\Datatables;
use App\Models\PermissionUser;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\UserAttemptQuiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    private $quiz = 21;
    private $user_attempt_quiz = 22;
    private $subadmin_menu_id = 19;


    public function index($id, $category_id)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $category = QuizCategory::where('id', $category_id)->first();

        return view('admin.quizQuestion.index', compact('pre', 'id', 'category_id', 'category'));
    }


    // public function questionList(Request $request)
    // {

    //     $data['pre'] = $pre = PermissionUsers::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //     if (!isset($pre) || empty($pre)) {
    //         return redirect('dashboard');
    //     }
    //     $questions = QuizQuestion::orderBy('id','DESC')->get();
    //     // dd($questions);
    //     $data['title']="Quiz Questions";
    //     if ($request->ajax()) {
    //         if(!empty($pre) && $pre->is_modify == 'yes'){
    //         return Datatables::of($questions)
    //                 ->addIndexColumn()
    //                 ->addColumn('action', function($questions){
    //                 		$btn = '<a href="'.url('question-options/'.$questions->id).'" class="edit btn btn-info btn-sm">Options</a> ';
    //                         $btn .= '<a href="'.url('edit-question/'.$questions->id).'" class="edit btn btn-success btn-sm">Edit</a> ';

    // 	                    $btn .= '<a href="javascript:void(0);" data-url="'.url('delete-question/'.$questions->id).'" class="edit btn btn-danger btn-sm" id="deleteQuestion">Delete</a> ';

    // 	                   return $btn;
    // 	                    })
    //                 ->rawColumns(['action'])
    //                 ->make(true);
    //             }
    //             else{
    //                 return Datatables::of($questions)
    //                     ->addIndexColumn()
    //                     ->make(true);
    //             }
    //                 }

    //             return view('admin.quiz-questions.index')->with($data);
    // }
    public function questionList(Request $request, $id, $category_id)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        if ($request->ajax()) {
            $questions = QuizQuestion::where('quiz_id', $id)->orderBy('id', 'DESC')->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($questions)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) use ($id, $category_id) {
                        $btn = "";
                        $btn .= '<a href="' . url('question-options/' . $row->id . '/' . $category_id . '/' . $row->quiz_id) . '" class="edit btn btn-info btn-sm">Options</a> ';
                        $btn .= '<a href="' . url('edit-question/' . $row->id . '/' . $row->quiz_id) . '" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a> ';

                        $btn .= '<a href="' . url("delete-question/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('category', function ($row) {
                        // if (is_array($row->quiz_category_id)) {
                        //     $categoryIds = $row->quiz_category_id;
                        // } elseif (is_string($row->quiz_category_id)) {
                        //     $categoryIds = explode(',', $row->quiz_category_id);
                        // } else {
                        //     $categoryIds = [$row->quiz_category_id];
                        // }

                        // // Clean up: remove empty/null values
                        // $categoryIds = array_filter($categoryIds);

                        // if (!empty($categoryIds)) {
                        //     $categoryNames = QuizCategory::whereIn('id', $categoryIds)->pluck('category_name')->toArray();
                        //     $categoryText = implode(', ', $categoryNames);

                        //     // Truncate to 100 words
                        //     $words = preg_split('/\s+/', $categoryText);
                        //     if (count($words) > 50) {
                        //         $categoryText = implode(' ', array_slice($words, 0, 50)) . '...';
                        //     }

                        //     return "<span class='plan category'>" . e($categoryText) . "</span>";
                        // }

                        // return "<span class='plan category'>N/A</span>";
                        $categoryNames = QuizCategory::where('id', $row->quiz_category_id)->value('category_name');
                        $plainTexttitle = strip_tags($categoryNames);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }

                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('image', function ($row) {
                        $imgPath = !empty($row->image)
                            ? asset('assets/images/' . $row->image)
                            : asset('assets/images/unnamed.jpg');
                        return '<img src="' . $imgPath . '" alt="Preview" width="40" height="40" class="border border-light rounded">';
                    })


                    ->editColumn('question', function ($row) {
                        $plainTextDescription = strip_tags($row->question);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })

                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['action', 'category', 'question', 'status', 'image'])
                    ->make(true);
            } else {
                return Datatables::of($questions)
                    ->addIndexColumn()
                    ->editColumn('category', function ($row) {
                        $categoryNames = QuizCategory::where('id', $row->quiz_category_id)->value('category_name');
                        $plainTexttitle = strip_tags($categoryNames);
                        $truncatedtitle = substr($plainTexttitle, 0, 50);

                        if (strlen($plainTexttitle) > 50) {
                            $truncatedtitle .= '...';
                        }
                        return "<span class='plan $truncatedtitle'>" . ucfirst($truncatedtitle) . "</span>";
                    })
                    ->editColumn('image', function ($row) {
                        if (!empty($row->image)) {
                            $imgPath = asset('assets/images/' . $row->image);
                            return '<img src="' . $imgPath . '" alt="Preview" width="40" height="40">';
                        }
                        return '<span>No Image</span>';
                    })
                    ->editColumn('question', function ($row) {
                        $plainTextDescription = strip_tags($row->question);
                        $truncatedDescription = substr($plainTextDescription, 0, 50);

                        if (strlen($plainTextDescription) > 50) {
                            $truncatedDescription .= '...';
                        }

                        return "<span class='plan'>" . ucfirst(e($truncatedDescription)) . "</span>";
                    })


                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['category', 'question', 'status', 'image'])
                    ->make(true);
            }
        }
    }
    public function addEmpathyQuestion($id, $category_id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!empty($pre) && $pre->is_modify == 'yes') {

            $data['title'] = 'Add Question';
            $data['category'] =  QuizCategory::where(['id' => $category_id, 'status' => 'active'])->first();
            $data['id'] = $id;
            $data['category_id'] = $category_id;

            $categoryName = $data['category']['category_name'];

            return view('admin.quizQuestion.create', compact('categoryName'))->with($data);
        }
        return redirect('home');
    }

    public function storeEmpathyQuestion(Request $request, $category_id, $id)
    {
        // dd($request->all());
        $rules = [
            'category' => 'required|exists:quiz_categories,id',
            'marks' => 'required|numeric|min:1|max:100',
        ];

        $rules['question'] = 'required|string|min:3|not_regex:/<[^>]*>/u';
        $rules['age'] = 'nullable';
        $rules['image'] = 'nullable';

        $messages = [
            'category.required' => 'The quiz category is required.',
            'category.exists' => 'The selected quiz category does not exist.',
            'marks.required' => 'Points are required.',
            'marks.numeric' => 'Points must be a number.',
            'marks.min' => 'Points must be at least 1.',
            'question.required' => 'The question field is required.',
            'question.string' => 'The question must be a valid text.',
            'question.min' => 'The question must not be less than 3 characters.',
            'question.max' => 'The question cannot exceed 500 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $quizCategory = QuizCategory::find($request->category);
        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time() .  $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('assets/images'), $imageName);
        }

        $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        $questionChinese = $translator->translate($request['question']);
        // dd($request->all());
        $quizQuestion =  QuizQuestion::create([
            'quiz_category_id' => $request['category'],
            'quiz_id' => $request['quiz_id'],
            'question' => $request['question'],
            'question_chinese' => $questionChinese,
            'marks' => $request['marks'],
            'color' => $quizCategory->color,
            'image' => $imageName,
            'age' => $request->age,
            'title_color' => $quizCategory->title_color,
            'status' => 'inactive'
        ]);

        // /** 🔔 Notification only to child users */
        // $childIds = User::where(['user_type' => 'child', 'status' => 'active'])
        //     ->pluck('id')->toArray();

        // $devices = DeviceToken::whereIn('user_id', $childIds)
        //     ->whereNotNull('token')
        //     ->get();

        // // Get notification content from template (instead of hardcoded title & message)
        // $content = getNotificationContent('add_question', [
        //     'category' => $quizCategory->category_name,
        //     'question'      => $quizQuestion->question,
        // ]);

        // $notification_type = "add_question";
        // $extra_data = [
        //     "question_id" => $quizQuestion->id,
        //     "question"    => $quizQuestion->question,
        //     "category"    => $quizCategory->category_name,
        //     'id' => $quizCategory->id,
        //     'color' => $quizCategory->color,
        //     'title_color' => $quizCategory->title_color,
        //     "type"        => "add_question"
        // ];
        // $user_type = "user";

        // foreach ($devices as $device) {
        //     sendNotificationSender(
        //         $device->user_id,
        //         $content['title'],
        //         $content['body'],
        //         $notification_type,
        //         $extra_data,
        //         $user_type
        //     );
        // }
        return redirect()->route('quiz-questions.index', ['id' => $request->quiz_id, 'category_id' => $request['category']])->with('added', 'Question Added Successfully!');
    }

    public function editEmpathyQuestion($id, $category_id)
    {
        // dd($id);
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['question'] = QuizQuestion::where('id', $id)->first();
            $data['category'] =  QuizCategory::where(['id' => $data['question']['quiz_category_id'], 'status' => 'active'])->first();
            $data['id'] = $id;
            $data['category_id'] = $category_id;
            $categoryName = $data['category']['category_name'];
            return view('admin.quizQuestion.edit', compact('categoryName'))->with($data);
        }
        return redirect('dashboard');
    }

    public function updateEmpathyQuestion(Request $request, $id)
    {
        $rules = [
            'marks' => 'required|numeric|min:1|max:100',
            'question' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'not_regex:/<[^>]*>/u',
                Rule::unique('quiz_questions', 'question')
                    ->where('quiz_category_id', $request->category)
                    ->ignore($id)
                    ->whereNull('deleted_at')
            ],
            'status' => 'required|in:active,inactive',
            'age' => 'nullable',
            'image' => 'nullable'
        ];

        $messages = [
            'marks.required' => 'Points are required.',
            'marks.numeric' => 'Points must be a number.',
            'marks.min' => 'Points must be at least 1.',
            'question.required' => 'The question field is required.',
            'question.string' => 'The question must be a valid text.',
            'question.unique' => 'The question already exists in this category.',
            'question.min' => 'The question must not be less than 3 characters.',
            'question.max' => 'The question cannot exceed 500 characters.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $quizCategory = QuizCategory::find($request->category);
        $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
        $questionChinese = $translator->translate($request['question']);
        $data = QuizQuestion::findOrFail($id);

        $imageName = $data->image;
        if ($request->hasFile('image')) {
            if ($imageName && file_exists(public_path('assets/images/' . $imageName))) {
                unlink(public_path('assets/images/' . $imageName)); // old image delete
            }
            $imageName = time() . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('assets/images'), $imageName);
        }

        $update = [
            'question' => $request['question'],
            'question_chinese' => $questionChinese,
            'quiz_id' => $request->quiz_id,
            'marks' => $request['marks'],
            'status' => $request['status'],
            'color' => $quizCategory->color,
            'age' => $request->age,
            'image' => $imageName,
            'title_color' => $quizCategory->title_color,
        ];
        $data->update($update);
        return redirect()->route('quiz-questions.index', ['id' => $request->quiz_id, 'category_id' => $request['category']])->with('added', 'Question Added Successfully!');
    }
    public function deleteEmpathyQuestion($id)
    {
        $question = QuizQuestion::find($id);

        if ($question) {
            $question->status = 'inactive';
            $question->save();

            $question->delete();
        }
        return redirect()->back()->with('success', 'Question has been marked as inactive and soft deleted.');
    }

    public function questionOptionList(Request $request, $id, $category_id, $quiz_id)
    {

        // dd($request->ajax() , $request->all(),$id);
        // Fetch options grouped by question_id
        $options = QuizQuestionOption::where('question_id', $id)
            ->orderBy('id', 'DESC')
            ->get()
            ->groupBy('question_id');

        $data['title'] = "Quiz Questions Options";

        if ($request->ajax()) {
            return Datatables::of($options)
                ->addIndexColumn()
                ->addColumn('question', function ($groupedOptions) {
                    $questionText = optional($groupedOptions->first()->question)->question ?? '-';
                    return Str::limit($questionText, 50, '...');
                })
                ->addColumn('options', function ($groupedOptions) {
                    $optionsText = $groupedOptions->pluck('option_text')->implode(', ');
                    return Str::limit($optionsText, 50, '...');
                })
                ->addColumn('correct_option', function ($groupedOptions) {
                    $correctOption = $groupedOptions->where('is_correct', 1)->pluck('option_text')->implode(', ');
                    $truncated = Str::limit($correctOption, 50, '...');
                    return $correctOption ? "<b>$truncated</b> ✅" : '-';
                })
                ->addColumn('action', function ($groupedOptions)  use ($category_id, $quiz_id) {
                    $questionId = $groupedOptions->first()->question_id;
                    $id = $groupedOptions->first()->id;
                    $btn = '';
                    // if (!empty($pre) && $pre->is_modify == 'yes') {
                    $btn .= '<a href="' . url('edit-question-options/' . $questionId . '/' . $category_id . '/' . $quiz_id) . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-pencil"></span></a> ';
                    $btn .= '<a href="' . url('delete-question-options/' . $questionId . '/' . $category_id) . '" class="delete" title="Delete" data-id="' . $questionId . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                    return $btn;
                    // }
                    // return $btn;
                })
                ->rawColumns(['correct_option', 'action'])
                ->make(true);
        }
        $data['id'] = $id;
        $data['category_id'] = $category_id;
        $data['options'] = $options;
        $data['quiz_id'] = $quiz_id;

        return view('admin.quiz-options.index')->with($data);
    }



    public function addQuestionOptions($id, $category_id, $quiz_id)
    {

        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->quiz);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $marks = QuizQuestion::where('id', $id)->first();
            $data['question_id'] = $id;
            $data['category_id'] = $category_id;
            $data['quiz_id'] = $quiz_id;
            $data['marks'] = $marks->marks;
            $data['title'] = 'Add Options';
            return view('admin.quiz-options.create')->with($data);
        }
        return redirect('home');
    }


    public function storeQuestionOptions(Request $request, $category_id, $quiz_id)
    {
        $validators = Validator::make($request->all(), [
            'options'      => 'required|array|min:2|max:4',
            'options.*'    => 'required|string|min:1|max:255|not_regex:/<[^>]*>/',
            'is_correct'   => 'required|integer|between:0,3',
            'description'  => 'required|string|min:3|not_regex:/<[^>]*>/',
            // 'description_chinese' => 'required|string|min:3',
        ], [
            'options.required' => 'At least two options are required.',
            'options.array'    => 'Options must be an array.',
            'options.min'      => 'At least two options are required.',
            'options.max'      => 'No more than four options are allowed.',

            'options.*.required' => 'Each option is required.',
            'options.*.string'   => 'Each option must be a string.',
            'options.*.min'      => 'Each option must be at least 1 character.',
            'options.*.max'      => 'Each option must not exceed 100 characters.',
            'options.*.not_regex' => 'Invlid value in options.',

            'is_correct.required' => 'Correct answer index is required.',
            'is_correct.integer'  => 'Correct answer index must be an integer.',
            'is_correct.between'  => 'Correct answer index must be between 0 and 3.',

            'description.required' => 'Description is required.',
            'description.string'   => 'Description must be a string.',
            'description.min'      => 'Description must be at least 3 characters.',
            'description.max'      => 'Description must not exceed 500 characters.',
            'description.not_regex' => 'Invlid value in the description.',
        ]);

        if ($validators->passes()) {
            foreach ($request->options as $index => $option) {
                $translator = new GoogleTranslate('zh');
                $optionChinese = $translator->translate($option);

                $isCorrect = ($index == $request->is_correct) ? 1 : 0;

                // Only include description if it's the correct option
                $data = [
                    'question_id' => $request['question_id'],
                    'option_text' => $option,
                    // 'option_text_chinese' => $optionChinese ?? $option,
                    'is_correct' => $isCorrect,
                ];

                if ($isCorrect == 1) {
                    $data['option_text'] = $option;
                    $data['description'] = $request['description'];
                    // $data['option_text_chinese'] = $request->description_chinese;
                }

                QuizQuestionOption::create($data);
            }

            //Question active कर दो
            $quiz = Quiz::find($request->quiz_id);
            $quiz->update(['status' => 'active']);

            $quizQuestion = QuizQuestion::find($request->question_id);
            $quizQuestion->update(['status' => 'active']);

            //  Notification send 
            // $quizCategory = QuizCategory::find($quizQuestion->quiz_category_id);
            // $childUsers = User::where(['user_type' => 'child', 'status' => 'active'])->get();
            // $filteredChildIds = $childUsers->filter(function ($child) use ($quiz) {
            //     if (!$quiz->age) {
            //         return true;  
            //     }
            //     if (empty($child->dob)) {
            //         return false;  
            //     }
            //     $childAge = Carbon::parse($child->dob)->age;
            //     if (strpos($quiz->age, '-') !== false) {
            //         [$minAge, $maxAge] = explode('-', $quiz->age);
            //         return $childAge >= (int)$minAge && $childAge <= (int)$maxAge;
            //     }
            //     return $childAge == (int)$quiz->age;
            // })->pluck('id')->toArray();
            // if (!empty($filteredChildIds)) {
            //     $devices = DeviceToken::whereIn('user_id', $filteredChildIds)
            //         ->whereNotNull('token')
            //         ->get()
            //         ->unique('user_id');  
            //     $content = getNotificationContent('add_question', [
            //         'category' => $quizCategory->category_name ?? '',
            //         'question' => $quizQuestion->question ?? '',
            //     ]);
            //     $extra_data = [
            //         "question_id" => $quizQuestion->id,
            //         "question"    => $quizQuestion->question,
            //         "category"    => $quizCategory->category_name ?? '',
            //         "id"          => $quizCategory->id ?? null,
            //         "color"       => $quizCategory->color ?? '',
            //         "title_color" => $quizCategory->title_color ?? '',
            //         "type"        => "add_question"
            //     ];
            //     $user_type = "user";
            //     $sentUsers = [];
            //     foreach ($devices as $device) {
            //         if (in_array($device->user_id, $sentUsers)) continue;  
            //         sendNotificationSender(
            //             $device->user_id,
            //             $content['title'] ?? 'New Question',
            //             $content['body'] ?? 'A new quiz question has been added.',
            //             'add_question',
            //             $extra_data,
            //             $user_type
            //         );
            //         $sentUsers[] = $device->user_id;
            //     }
            // }
            return redirect()->route('question-options', [
                'id' => $request['question_id'],
                'category_id' => $category_id,
                'quiz_id' => $quiz_id
            ])->with('added', 'Options added successfully!');
        } else {
            return redirect()->back()->withErrors($validators->errors())->withInput();
        }
    }

    // public function editQuestionOption($question_id, $category_id)
    // {
    //     $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //     if (!empty($pre) && $pre->is_modify == 'yes') {
    //         $marks = QuizQuestion::where('id', $question_id)->first();
    //         $data['question_id'] = $question_id;
    //         $data['category_id'] = $category_id;
    //         $data['marks'] = $marks->marks;
    //         $data['options'] = QuizQuestionOption::where('question_id', $question_id)->get();
    //         $data['title'] = 'Edit Options';

    //         return view('admin.quiz-options.edit')->with($data);
    //     }
    //     return redirect('dashboard');
    // }

    public function editQuestionOption($question_id, $category_id, $quiz_id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $marks = QuizQuestion::where('id', $question_id)->first();
            $options = QuizQuestionOption::where('question_id', $question_id)->get();

            // ✅ Find which option is marked as correct
            $correct_option_index = $options->search(function ($opt) {
                return $opt->is_correct == 1;
            });

            $data = [
                'quiz_id' => $quiz_id,
                'question_id' => $question_id,
                'category_id' => $category_id,
                'marks' => $marks->marks,
                'options' => $options,
                'correct_option_index' => $correct_option_index,
            ];

            return view('admin.quiz-options.edit')->with($data);
        }
        return redirect('dashboard');
    }


    public function updateQuestionOption(Request $request, $category_id, $quiz_id)

    {
        // dd($request->all());
        $validators = Validator::make($request->all(), [
            'options'   => 'required|array|min:2|max:4',
            'options.*' => 'required|string|min:1|max:255|not_regex:/<[^>]*>/',

            'is_correct' => 'required|integer|between:0,3',

            // 'descriptions' => 'nullable|array',
            // 'descriptions.' . $request->is_correct => 'required|string|min:3|not_regex:/<[^>]*>/',

            // 'descriptions_chinese' => 'nullable|array',
            // 'descriptions_chinese.' . $request->is_correct => 'required|string|min:3',
        ], [
            'options.*.required' => 'Each option is required.',
            'options.*.string'   => 'Each option must be a string.',
            'options.*.min'      => 'Each option must be at least 1 character.',
            'options.*.max'      => 'Each option must not exceed 100 characters.',
            'options.*.not_regex' => 'Invalid value in options.',

            'is_correct.required' => 'Correct answer index is required.',
            'is_correct.integer'  => 'Correct answer index must be an integer.',
            'is_correct.between'  => 'Correct answer index must be between 0 and 3.',

            'descriptions.' . $request->is_correct . '.required' => 'Explanation is required for the correct answer.',
            'descriptions.' . $request->is_correct . '.string' => 'Explanation must be a string.',
            'descriptions.' . $request->is_correct . '.not_regex' => 'Invalid value in the explanation.',

            // 'descriptions_chinese.' . $request->is_correct . '.required' => 'Chinese explanation is required for the correct answer.',
            // 'descriptions_chinese.' . $request->is_correct . '.min' => 'Chinese explanation must be at least 3 characters.',
        ]);


        if ($validators->passes()) {
            // dd($request->question_id);
            // Delete previous options
            QuizQuestionOption::where('question_id', $request->question_id)->delete();

            // Loop through each option and save it along with the corresponding description
            foreach ($request->options as $index => $option) {
                // Translate the option text into Chinese
                $translator = new GoogleTranslate('zh'); // 'zh' is the language code for Chinese
                $optionChinese = $translator->translate($option);
                $isCorrect = ($index == $request->is_correct) ? 1 : 0;
                // Save the option
                $quizOption = QuizQuestionOption::create([
                    'question_id' => $request['question_id'],
                    'option_text' => $option,
                    // 'option_text_chinese' => $optionChinese ?? $option,
                    'is_correct' => $isCorrect,
                ]);

                // If this option is the correct one, update the description fields
                if ($isCorrect == 1) {
                    $quizOption->update([
                        'option_text' => $option, // Store the English explanation
                        'description' => $request->descriptions[$index] ?? 'N/A',
                        // 'option_text_chinese' => $request->descriptions_chinese[$index], // Store the Chinese explanation
                    ]);
                }
            }

            return redirect()->route('question-options', ['id' => $request['question_id'], 'category_id' => $request->category_id, 'quiz_id' => $request->quiz_id])->with('updated', 'Options updated successfully!');
        } else {
            return \Redirect::back()->withErrors($validators->errors())->withInput();
        }
    }

    public function deleteQuestionOption($id)
    {
        $options = QuizQuestionOption::where('question_id', $id)->get();

        if ($options->isNotEmpty()) {
            foreach ($options as $option) {
                $option->delete(); // Soft delete
            }

            return redirect()->back()->with('success', 'Question options has been marked as inactive and soft deleted.');
        }

        // @envelope-exempt. Web route (quiz-questions admin UI), not the mobile API - deliberately
        // left off the ApiResponse envelope. The snapshot gate does not cover it.
        return response()->json(['message' => 'No options found for this question.'], 404);
    }


    public function countQuestionOptions($id)
    {
        $optionCount = QuizQuestionOption::where('question_id', $id)->count();

        // Web route (admin UI), not the mobile API. See deleteQuestionOption. @envelope-exempt
        return response()->json(['count' => $optionCount]);
    }

    public function userAttemptQuizList(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(
            Auth::user()->id,
            $this->user_attempt_quiz
        );

        if (!$pre) {
            return redirect('home');
        }

        if ($request->ajax()) {

            // 🔥 Only CHILD users who attempted quiz
            $attempts = UserAttemptQuiz::with('user.parent')
                ->select('user_id')
                ->groupBy('user_id');

            // ================= FILTER =================

            // Normal Parent ke CHILD
            if ($request->parent_type === 'normal') {
                $attempts->whereHas('user', function ($q) {
                    $q->where('user_type', 'child')
                        ->whereHas('parent', function ($p) {
                            $p->whereNull('school_id');
                        });
                });
            }

            // School Parent ke CHILD
            if ($request->parent_type === 'school') {
                $attempts->whereHas('user', function ($q) {
                    $q->where('user_type', 'child')
                        ->whereHas('parent', function ($p) {
                            $p->whereNotNull('school_id');
                        });
                });
            }

            // Default → all CHILD
            if (!$request->filled('parent_type')) {
                $attempts->whereHas('user', function ($q) {
                    $q->where('user_type', 'child');
                });
            }

            $attempts = $attempts->get();

            // ================= DATATABLE =================
            if ($pre->is_modify === 'yes') {
                return Datatables::of($attempts)
                    ->addIndexColumn()
                    ->editColumn('user_name', fn($a) => $a->user->name ?? 'N/A')
                    ->addColumn(
                        'action',
                        fn($a) =>
                        '<a href="' . url('users-attempt-quiz-details/' . $a->user_id) .
                            '" class="btn btn-info btn-sm">Details</a>'
                    )
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return Datatables::of($attempts)
                ->addIndexColumn()
                ->editColumn('user_name', fn($a) => $a->user->name ?? 'N/A')
                ->make(true);
        }

        return view('admin.user-attempt-quizzes.index', $data);
    }



    // public function userAttemptQuizDetails($user_id,$id){

    //     $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //     if(!empty($pre) && $pre->is_view == 'yes'){
    //         $data['title']= 'User Attempt Quiz Details';
    //         $attempt = UserAttemptQuiz::where('user_id',$user_id)->where('quiz_category_id',$id)->get();
    //         foreach ($attempt as $option) {
    //         $data['category_name'] = QuizCategory::where('id',$option['quiz_category_id'])->value('category_name');
    //         $data['question_name'] = QuizQuestion::where('id',$option['question_id'])->value('question');
    //         return view('admin.user-attempt-quizzes.details')->with($data);
    //         }
    //         return redirect('dashboard');
    //     }
    // }

    //     public function userAttemptQuizDetails($user_id)
    //     {
    //         $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //         if (!empty($pre) && $pre->is_view == 'yes') {
    //         $data['title'] = 'User Attempt Quiz Details';
    //         $attempts = UserAttemptQuiz::where('user_id', $user_id)
    //                     ->where('quiz_category_id', $id)
    //                     ->get();

    //         if ($attempts->isEmpty()) {
    //             return redirect('dashboard')->with('error', 'No quiz attempts found.');
    //         }

    //         $data['category_name'] = QuizCategory::where('id', $id)->value('category_name');
    //         // dd($attempts);
    //         $questions = [];
    //         $total_marks = 0;
    //         $marks_obtained = 0;

    //         foreach ($attempts as $attempt) {
    //             // dd($attempt->question_id);
    //             $question = QuizQuestion::where('id', $attempt->question_id)->first();
    //             // dd($question);
    //             if ($question) {
    //                 $questions[] = [
    //                     'question' => $question->question,
    //                     // 'answer' => $attempt->user_answer,
    //                     'marks' => $attempt->marks_obtained,
    //                     'total_marks' => (int)$question->marks,
    //                 ];
    //                 $total_marks += $question->marks;
    //                 $marks_obtained += $attempt->marks_obtained;
    //             }
    //             // dd($questions);
    //         }

    //         $data['questions'] = $questions;
    //         $data['total_marks'] = $total_marks;
    //         $data['marks_obtained'] = $marks_obtained;
    //         return view('admin.user-attempt-quizzes.details', $data);
    //     }
    //     return redirect('dashboard')->with('error', 'Unauthorized access.');
    // }

    public function userAttemptQuizDetails($user_id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->user_attempt_quiz);
        if (empty($pre) || $pre->is_view != 'yes') {
            return redirect('dashboard')->with('error', 'Unauthorized access.');
        }

        $data['title'] = 'User Attempt Quiz Details';

        // Fetch all quizzes the user has attempted
        $quizzes = Quiz::whereHas('userAttemptQuizzes', function ($q) use ($user_id) {
            $q->where('user_id', $user_id);
        })->get();

        if ($quizzes->isEmpty()) {
            return redirect('dashboard')->with('error', 'No quiz attempts found.');
        }

        $data['quizzes'] = [];

        foreach ($quizzes as $quiz) {
            $attempts = UserAttemptQuiz::where('user_id', $user_id)
                ->where('quiz_id', $quiz->id)
                ->get();

            $questions = [];
            $total_marks = 0;
            $marks_obtained = 0;

            foreach ($attempts as $attempt) {
                $question = QuizQuestion::find($attempt->question_id);
                if ($question) {
                    $questions[] = [
                        'question' => $question->question,
                        'marks' => $attempt->marks_obtained,
                        'total_marks' => (int)$question->marks,
                    ];
                    $total_marks += $question->marks;
                    $marks_obtained += $attempt->marks_obtained;
                }
            }

            $data['quizzes'][] = [
                'quiz_name' => $quiz->title,
                'category_name' => $quiz->quizCategory->category_name ?? 'N/A',
                'questions' => $questions,
                'total_marks' => $total_marks,
                'marks_obtained' => $marks_obtained,
            ];
        }

        return view('admin.user-attempt-quizzes.details', $data);
    }

    // API Functions 
    // public function quizCategory(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $category = Category::select('id', 'category_name')->latest()->get();
    //     $questionCategory = QuizCategory::where('status', 'active')
    //         ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
    //         ->get()

    //         ->map(function ($category) use ($language) {
    //             $total_marks = QuizQuestion::where('quiz_category_id', $category->id)->where('status', 'active')->sum('marks');
    //             $attempt_marks = UserAttemptQuiz::where('user_id', auth()->user()->id)->whereIn('question_id', function ($query) use ($category) {
    //                 $query->select('id')
    //                     ->from('quiz_questions')
    //                     ->where('quiz_category_id', $category->id);
    //             })
    //                 ->sum('marks_obtained');

    //             return [
    //                 'id' => $category->id,
    //                 'category_name' => $language === 'english' ? $category->category_name : $category->category_name_chinese,
    //                 'banner_image' => asset('assets/images/' . $category->banner_image),
    //                 'marks_obtained' => (int)$attempt_marks,
    //                 'total_marks' => $total_marks,
    //                 'color' => $category->color,
    //                 'title_color' => $category->title_color,


    //             ];
    //         });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Question category fetched successfully!',
    //         'data' => $questionCategory,
    //         'category' => $category,
    //     ]);
    // }



    // public function quizQuestionList(Request $request) {
    //     $language = $request->language;

    //     $questions = QuizQuestion::where('quiz_category_id', $request->quiz_category_id)
    //     ->where('status', 'active')
    //     ->get()->toArray();
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Question  fetched successfully!',
    //         'data' => $questions
    //     ]);
    // }

    // public function quizQuestion(Request $request)
    // {

    //     if (!$request->quiz_category_id) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'quiz_category_id is required',
    //             'data' => null
    //         ]);
    //     }
    //     $language = $request->language ?? 'english';
    //     $questions = QuizQuestion::where('quiz_category_id', $request->quiz_category_id)
    //         ->where('status', 'active')
    //         ->with('options') // Eager loading for performance
    //         ->get();
    //     if ($questions->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No questions found for this category',
    //             'data' => null
    //         ]);
    //     }
    //     $response = $questions->map(function ($question) use ($language) {
    //         $questionName = $language === 'english' ? $question->question : $question->question_chinese;

    //         $options = $question->options->map(function ($option) use ($language) {
    //             return [
    //                 'id' => $option->id,
    //                 'color' => $option->color,
    //                 'title_color' => $option->title_color,
    //                 'option_text' => $language === 'english' ? $option->option_text : $option->option_text_chinese,
    //                 'is_correct' => $option->is_correct,
    //                 'description' => $language === 'english' ? $option->description : $option->description_chinese
    //             ];
    //         });
    //         $attempt_question = UserAttemptQuiz::where('user_id', auth()->user()->id)->where('question_id', $question->id)->first();
    //         if ($attempt_question) {
    //             // $is_attempt = 'yes';
    //             $is_attempt = 'no';
    //         } else {
    //             $is_attempt = 'no';
    //         }
    //         $correctOption = $options->where('is_correct', 1)->first();
    //         $quiCategory = QuizCategory::where('id', $question->quiz_category_id)->first();
    //         return [
    //             'id' => $question->id,
    //             'question_name' => $questionName,
    //             'banner_image' => $quiCategory->banner_image
    //                 ? asset('assets/images/' . $quiCategory->banner_image)
    //                 : asset('assets/images/default-banner.jpg'),
    //             'question_image' => $question->image  ? asset('assets/images/' . $question->image) : null,
    //             'quiz_category_id' => $question->quiz_category_id,
    //             'marks' => $question->marks,
    //             'age' => $question->age,
    //             'color' => $question->color,
    //             'title_color' => $question->title_color,
    //             'status' => $question->status,
    //             'options' => $options,
    //             'correct_option' => $correctOption ? $correctOption['option_text'] : null,
    //             'is_attempt' => $is_attempt,
    //             // 'selected_option_id' => $attempt_question->selected_option_id ?? null,
    //             'selected_option_id' =>  null,
    //         ];
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'data' => $response
    //     ]);
    // }



    // public function quizQuestion(Request $request)
    // {
    //     if (!$request->quiz_category_id) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'quiz_category_id is required',
    //             'data' => null
    //         ]);
    //     }

    //     $auth = auth()->user();
    //     $language = $request->language ?? 'english';

    //     //  Calculate user age from DOB
    //     $userAge = null;
    //     if (!empty($auth->dob)) {
    //         $userAge = Carbon::parse($auth->dob)->age;
    //     }

    //     $questions = QuizQuestion::where('quiz_category_id', $request->quiz_category_id)
    //         ->where('status', 'active')
    //         ->with('options')
    //         ->get();

    //     if ($questions->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No questions found for this category',
    //             'data' => null
    //         ]);
    //     }

    //     //  Age Filter
    //     $filteredQuestions = $questions->filter(function ($question) use ($userAge) {
    //         if (!$userAge || !$question->age) {
    //             return true; // Show if user has no DOB or question has no age filter
    //         }

    //         // Parse age range like "11-14"
    //         if (strpos($question->age, '-') !== false) {
    //             [$minAge, $maxAge] = explode('-', $question->age);
    //             return $userAge >= (int) $minAge && $userAge <= (int) $maxAge;
    //         }

    //         // Single age value (e.g., "12")
    //         return $userAge == (int) $question->age;
    //     });

    //     if ($filteredQuestions->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No questions found for your age group',
    //             'data' => null
    //         ]);
    //     }

    //     $response = $filteredQuestions->map(function ($question) use ($language) {
    //         $questionName = $language === 'english'
    //             ? $question->question
    //             : $question->question_chinese;

    //         $options = $question->options->map(function ($option) use ($language) {
    //             return [
    //                 'id' => $option->id,
    //                 'color' => $option->color,
    //                 'title_color' => $option->title_color,
    //                 'option_text' => $language === 'english'
    //                     ? $option->option_text
    //                     : $option->option_text_chinese,
    //                 'is_correct' => $option->is_correct,
    //                 'description' => $language === 'english'
    //                     ? $option->description
    //                     : $option->description_chinese
    //             ];
    //         });

    //         $attempt_question = UserAttemptQuiz::where('user_id', auth()->user()->id)
    //             ->where('question_id', $question->id)
    //             ->first();

    //         $is_attempt = $attempt_question ? 'no' : 'no';

    //         $correctOption = $options->where('is_correct', 1)->first();
    //         $quizCategory = QuizCategory::find($question->quiz_category_id);

    //         return [
    //             'id' => $question->id,
    //             'question_name' => $questionName,

    //             // ✅ Agar question image hai to wahi banner me show ho, warna category ka banner, warna default
    //             'banner_image' => $question->image
    //                 ? asset('assets/images/' . $question->image)
    //                 : ($quizCategory->banner_image
    //                     ? asset('assets/images/' . $quizCategory->banner_image)
    //                     : asset('assets/images/default-banner.jpg')),

    //             'quiz_category_id' => $question->quiz_category_id,
    //             'marks' => $question->marks,
    //             'age' => $question->age,
    //             'color' => $question->color,
    //             'title_color' => $question->title_color,
    //             'status' => $question->status,
    //             'options' => $options,
    //             'correct_option' => $correctOption ? $correctOption['option_text'] : null,
    //             'is_attempt' => $is_attempt,
    //             // 'selected_option_id' => $attempt_question->selected_option_id ?? null,
    //             'selected_option_id' => null,
    //         ];
    //     });



    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'data' => $response->values() // reset keys for JSON
    //     ]);
    // }



    // public function userAttemptQuiz(Request $request)
    // {
    //     // if($request->success == 'true'){

    //     $quiz_question_id = $request->question_id;
    //     $option_id = $request->option_id;

    //     $userId = auth()->user()->id;
    //     $quizCategoryId = $request->quiz_category_id;
    //     $questionId = $request->attempt_question_id;
    //     $selectedOptionId = $request->selected_option_id;
    //     $totalMarks = 0;

    //     // foreach ($attempts as $attempt) {
    //     // $questionId = $attempt['question_id'];
    //     // $selectedOptionId = $attempt['selected_option_id'];

    //     // Get correct option
    //     $correctOption = QuizQuestionOption::where('question_id', $questionId)
    //         ->where('is_correct', 1)
    //         ->first();
    //     $question = QuizQuestion::where('id', $questionId)->first();
    //     // Check if selected option is correct
    //     $marksObtained = ($correctOption && $correctOption->id == $selectedOptionId) ? $question->marks : 0;
    //     $totalMarks += $marksObtained;
    //     $user = User::where('id', auth()->user()->id)->first();
    //     $user->loyalty_points += $totalMarks;
    //     $user->save();
    //     // dd($marksObtained);
    //     // Store user attempt
    //     UserAttemptQuiz::create([
    //         'user_id' => $userId,
    //         'quiz_category_id' => $quizCategoryId,
    //         'question_id' => $questionId,
    //         'selected_option_id' => $selectedOptionId,
    //         'marks_obtained' => $marksObtained,
    //     ]);
    //     // }
    //     // dd($marksObtained);
    //     // if ($marksObtained > 0) {
    //     battery_adjust($user, +$marksObtained, 'quiz', [
    //         'quiz_category_id' => $quizCategoryId,
    //         'question_id' => $questionId,
    //         'selected_option' => $selectedOptionId
    //     ]);
    //     // }     
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Quiz attempt saved successfully!',
    //         'total_marks' => $totalMarks
    //     ]);
    //     // }else{
    //     //     return response()->json([
    //     //         'status' => true,
    //     //         'message' => 'Quiz attempt saved successfully!',
    //     //         'total_marks' => 0
    //     //     ]);
    //     // }
    // }
    // public function userAttemptQuiz(Request $request)
    // {
    //     $userId = auth()->user()->id;
    //     $quizCategoryId = $request->quiz_category_id;
    //     $questionId = $request->attempt_question_id;
    //     $selectedOptionId = $request->selected_option_id;

    //     // Get correct option and question
    //     $correctOption = QuizQuestionOption::where('question_id', $questionId)
    //         ->where('is_correct', 1)
    //         ->first();
    //     $question = QuizQuestion::find($questionId);

    //     if (!$question) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Question not found.',
    //             'marks_obtained' => 0
    //         ], 404);
    //     }

    //     // Determine marks (0 if wrong)
    //     $marksObtained = ($correctOption && $correctOption->id == $selectedOptionId) ? (int)$question->marks : 0;

    //     // ❌ Wrong answer → don’t save
    //     if ($marksObtained === 0) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Incorrect answer — not saved.',
    //             'marks_obtained' => 0
    //         ]);
    //     }

    //     $user = User::find($userId);
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found.',
    //             'marks_obtained' => 0
    //         ], 404);
    //     }

    //     // Find existing attempt
    //     $attempt = UserAttemptQuiz::where('user_id', $userId)
    //         ->where('quiz_category_id', $quizCategoryId)
    //         ->where('question_id', $questionId)
    //         ->first();

    //     if ($attempt) {
    //         // ✅ Always update attempt (refresh last option)
    //         $previousMarks = $attempt->marks_obtained;
    //         $attempt->update([
    //             'selected_option_id' => $selectedOptionId,
    //             'marks_obtained' => $marksObtained,
    //         ]);

    //         // Only add points if new score is higher
    //         if ($marksObtained > $previousMarks) {
    //             $delta = $marksObtained - $previousMarks;
    //             $user->loyalty_points += $delta;
    //             $user->save();

    //             battery_adjust($user, +$delta, 'quiz', [
    //                 'quiz_category_id' => $quizCategoryId,
    //                 'question_id' => $questionId,
    //                 'selected_option' => $selectedOptionId
    //             ]);
    //         }
    //     } else {
    //         // First correct attempt
    //         UserAttemptQuiz::create([
    //             'user_id' => $userId,
    //             'quiz_category_id' => $quizCategoryId,
    //             'question_id' => $questionId,
    //             'selected_option_id' => $selectedOptionId,
    //             'marks_obtained' => $marksObtained,
    //         ]);

    //         $user->loyalty_points += $marksObtained;
    //         $user->save();

    //         battery_adjust($user, +$marksObtained, 'quiz', [
    //             'quiz_category_id' => $quizCategoryId,
    //             'question_id' => $questionId,
    //             'selected_option' => $selectedOptionId
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Quiz attempt processed successfully.',
    //         'marks_obtained' => $marksObtained
    //     ]);
    // }
    // public function userAttemptQuiz(Request $request)
    // {
    //     $userId = auth()->user()->id;
    //     $quizCategoryId = $request->quiz_category_id;
    //     $questionId = $request->attempt_question_id;
    //     $selectedOptionId = $request->selected_option_id;

    //     // Get correct option and question
    //     $correctOption = QuizQuestionOption::where('question_id', $questionId)
    //         ->where('is_correct', 1)
    //         ->first();
    //     $question = QuizQuestion::find($questionId);

    //     if (!$question) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Question not found.',
    //             'marks_obtained' => 0
    //         ], 404);
    //     }

    //     //Determine marks (0 if wrong)
    //     $marksObtained = ($correctOption && $correctOption->id == $selectedOptionId) ? (int)$question->marks : 0;

    //     //Wrong answer → don’t save
    //     if ($marksObtained === 0) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Incorrect answer — not saved.',
    //             'marks_obtained' => 0
    //         ]);
    //     }

    //     $user = User::find($userId);
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found.',
    //             'marks_obtained' => 0
    //         ], 404);
    //     }

    //     //Find existing attempt
    //     $attempt = UserAttemptQuiz::where('user_id', $userId)
    //         ->where('quiz_category_id', $quizCategoryId)
    //         ->where('question_id', $questionId)
    //         ->first();

    //     if ($attempt) {
    //         $previousMarks = $attempt->marks_obtained;

    //         // ✅ Always update attempt (refresh last option)
    //         $attempt->update([
    //             'selected_option_id' => $selectedOptionId,
    //             'marks_obtained' => $marksObtained,
    //         ]);

    //         // Loyalty points only if improved
    //         if ($marksObtained > $previousMarks) {
    //             $delta = $marksObtained - $previousMarks;
    //             $user->loyalty_points += $delta;
    //             $user->save();
    //         }

    //         // 🔋 Battery increase only if this is the 2nd correct attempt
    //         if (!$attempt->battery_incremented_twice) {
    //             battery_adjust($user, +1, 'quiz', [
    //                 'quiz_category_id' => $quizCategoryId,
    //                 'question_id' => $questionId,
    //                 'selected_option' => $selectedOptionId
    //             ]);

    //             // mark that battery incremented second time
    //             $attempt->battery_incremented_twice = true;
    //             $attempt->save();
    //         }
    //     } else {
    //         // First correct attempt
    //         UserAttemptQuiz::create([
    //             'user_id' => $userId,
    //             'quiz_category_id' => $quizCategoryId,
    //             'question_id' => $questionId,
    //             'selected_option_id' => $selectedOptionId,
    //             'marks_obtained' => $marksObtained,
    //             'battery_incremented_twice' => false, // custom column in table
    //         ]);

    //         $user->loyalty_points += $marksObtained;
    //         $user->save();

    //         // 🔋 Battery increase on first correct attempt
    //         battery_adjust($user, +1, 'quiz', [
    //             'quiz_category_id' => $quizCategoryId,
    //             'question_id' => $questionId,
    //             'selected_option' => $selectedOptionId
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Quiz attempt processed successfully.',
    //         'marks_obtained' => $marksObtained
    //     ]);
    // }

    public function quizCategory(Request $request)
    {
        $language = $request->language ?? 'english';

        // Main parent categories (optional)
        $category = Category::select('id', 'category_name')->latest()->get();

        $questionCategory = QuizCategory::where('status', 'active')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->get()
            ->map(function ($quizCategory) use ($language) {

                // Get all quizzes under this category
                $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');

                // Calculate total marks from all questions in these quizzes
                $total_marks = QuizQuestion::whereIn('quiz_id', $quizIds)
                    ->where('status', 'active')
                    ->sum('marks');

                // Get attempt marks from UserAttemptQuiz filtering by quiz_id
                $attempt_marks = UserAttemptQuiz::where('user_id', auth()->id())
                    ->whereIn('quiz_id', $quizIds)
                    ->sum('marks_obtained');

                return [
                    'id' => $quizCategory->id,
                    'category_name' => $language === 'english'
                        ? $quizCategory->category_name
                        : $quizCategory->category_name_chinese,
                    'banner_image' => getImagePathUrl($quizCategory->banner_image, 'assets/images'),
                    'marks_obtained' => (int) $attempt_marks,
                    'total_marks' => (int) $total_marks,
                    'color' => $quizCategory->color,
                    'title_color' => $quizCategory->title_color,
                ];
            });

        return ApiResponse::success(
            $questionCategory,
            'Question category fetched successfully!',
            200,
            [],
            // $extra, not $legacy: `category` is a second list the app reads,
            // not an alias of data, so v2 must keep receiving it.
            ['category' => $category]
        );
    }

    public function quiz(Request $request)
    {
        if (!$request->quiz_category_id) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'quiz_category_id is required',
                'data' => null
            ], 200);
        }

        $auth = auth()->user();
        $language = $request->language ?? 'english';
        $userAge = $auth->dob ? \Carbon\Carbon::parse($auth->dob)->age : null;

        $quizzes = Quiz::where('quiz_category_id', $request->quiz_category_id)
            ->where('status', 'active')
            ->get();

        if ($quizzes->isEmpty()) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'No quizzes found for this category',
                'data' => null
            ], 200);
        }

        $filteredQuizzes = $quizzes->filter(function ($quiz) use ($userAge) {
            if (!$userAge || !$quiz->age) return true;

            if (strpos($quiz->age, '-') !== false) {
                [$minAge, $maxAge] = explode('-', $quiz->age);
                return $userAge >= (int) $minAge && $userAge <= (int) $maxAge;
            }

            return $userAge == (int) $quiz->age;
        });

        if ($filteredQuizzes->isEmpty()) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'No quizzes available for your age group',
                'data' => null
            ]);
        }

        $response = $filteredQuizzes->map(function ($quiz) {
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'quiz_category_id' => $quiz->quiz_category_id,
                'age' => $quiz->age,
                'color' => $quiz->color,
                'title_color' => $quiz->title_color,
                'image' => $quiz->image
                    ? getImagePathUrl($quiz->image, 'assets/images')
                    : asset('assets/images/default-banner.jpg'),
                'status' => $quiz->status,
            ];
        });

        return ApiResponse::success($response->values(), 'Quiz list fetched successfully!', 200);
    }


    public function quizQuestion(Request $request)
    {
        if (!$request->quiz_id) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'quiz_id is required',
                'data' => null
            ]);
        }

        $auth = auth()->user();
        $language = $request->language ?? 'english';
        $userAge = $auth->dob ? Carbon::parse($auth->dob)->age : null;

        $questions = QuizQuestion::where('quiz_id', $request->quiz_id)
            ->where('status', 'active')
            ->with('options')
            ->get();

        if ($questions->isEmpty()) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'No questions found for this quiz',
                'data' => null
            ]);
        }

        $filteredQuestions = $questions->filter(function ($question) use ($userAge) {
            if (!$userAge || !$question->age) return true;

            if (strpos($question->age, '-') !== false) {
                [$minAge, $maxAge] = explode('-', $question->age);
                return $userAge >= (int) $minAge && $userAge <= (int) $maxAge;
            }

            return $userAge == (int) $question->age;
        });

        if ($filteredQuestions->isEmpty()) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => 'No questions available for your age group',
                'data' => null
            ]);
        }

        $response = $filteredQuestions->map(function ($question) use ($language) {
            $options = $question->options->map(function ($option) use ($language) {
                return [
                    'id' => $option->id,
                    'color' => $option->color,
                    'title_color' => $option->title_color,
                    'option_text' => $language === 'english'
                        ? $option->option_text
                        : $option->option_text_chinese,
                    'is_correct' => $option->is_correct,
                    'description' => $language === 'english'
                        ? $option->description
                        : $option->description_chinese
                ];
            });

            $attempt = UserAttemptQuiz::where('user_id', auth()->id())
                ->where('quiz_id', $question->quiz_id)
                ->where('question_id', $question->id)
                ->first();

            return [
                'id' => $question->id,
                'question_name' => $language === 'english'
                    ? $question->question
                    : $question->question_chinese,
                'banner_image' => $question->image
                    ? getImagePathUrl($question->image, 'assets/images')
                    : asset('assets/images/default-banner.jpg'),
                'quiz_id' => $question->quiz_id,
                'marks' => $question->marks,
                'age' => $question->age,
                'color' => $question->color,
                'title_color' => $question->title_color,
                'options' => $options,
                'correct_option' => optional($options->where('is_correct', 1)->first())['option_text'],
                'is_attempt' => $attempt ? 'no' : 'no',
                // 'selected_option_id' => $attempt->selected_option_id ?? null,
                'selected_option_id' => null,
            ];
        });

        return ApiResponse::success($response->values(), 'Questions fetched successfully!', 200);
    }

    public function userAttemptQuiz(Request $request)
    {
        $userId = auth()->id();
        $quizId = $request->quiz_id;
        $questionId = $request->attempt_question_id;
        $selectedOptionId = $request->selected_option_id;

        if (!$quizId || !$questionId) {
            return ApiResponse::error(
                'quiz_id, attempt_question_id and selected_option_id are required',
                400,
                null,
                ['marks_obtained' => 0],
                ['marks_obtained' => 0]
            );
        }

        $question = QuizQuestion::where('quiz_id', $quizId)->find($questionId);
        if (!$question) {
            return ApiResponse::error(
                'Question not found for this quiz.',
                200,
                null,
                ['marks_obtained' => 0],
                ['marks_obtained' => 0]
            );
        }

        $correctOption = QuizQuestionOption::where('question_id', $questionId)
            ->where('is_correct', 1)
            ->first();

        $marksObtained = ($correctOption && $correctOption->id == $selectedOptionId)
            ? (int) $question->marks
            : 0;

        if ($marksObtained === 0) {
            return ApiResponse::success(
                ['marks_obtained' => 0],
                'Incorrect answer — not saved.',
                200,
                ['marks_obtained' => 0]
            );
        }

        $user = User::find($userId);
        if (!$user) {
            return ApiResponse::error(
                'User not found.',
                200,
                null,
                ['marks_obtained' => 0],
                ['marks_obtained' => 0]
            );
        }

        $attempt = UserAttemptQuiz::where('user_id', $userId)
            ->where('quiz_id', $quizId)
            ->where('question_id', $questionId)
            ->first();

        if ($attempt) {
            $previousMarks = $attempt->marks_obtained;
            $attempt->update([
                'selected_option_id' => $selectedOptionId,
                'marks_obtained' => $marksObtained,
            ]);

            if ($marksObtained > $previousMarks) {
                $user->loyalty_points += $marksObtained - $previousMarks;
                $user->save();
            }

            if (!$attempt->battery_incremented_twice) {
                battery_adjust($user, +1, 'quiz', [
                    'quiz_id' => $quizId,
                    'question_id' => $questionId,
                    'selected_option' => $selectedOptionId
                ]);
                $attempt->battery_incremented_twice = true;
                $attempt->save();
            }
        } else {
            UserAttemptQuiz::create([
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'question_id' => $questionId,
                'selected_option_id' => $selectedOptionId,
                'marks_obtained' => $marksObtained,
                'battery_incremented_twice' => false,
            ]);

            $user->loyalty_points += $marksObtained;
            $user->save();

            battery_adjust($user, +1, 'quiz', [
                'quiz_id' => $quizId,
                'question_id' => $questionId,
                'selected_option' => $selectedOptionId
            ]);
        }

        return ApiResponse::success(
            ['marks_obtained' => $marksObtained],
            'Quiz attempt processed successfully.',
            200,
            ['marks_obtained' => $marksObtained]
        );
    }

    // public function quizCompletionContent(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $category_id = $request->quiz_category_id;

    //     $data = [];
    //     $data['attempt_quizz_marks'] = UserAttemptQuiz::where('user_id', auth()->user()->id)
    //         ->where('quiz_category_id', $category_id)
    //         ->sum('marks_obtained');

    //     // Fetch category and decode referred_video
    //     $category = QuizCategory::where('id', $category_id)->first();

    //     if ($category) {
    //         // Decode referred_video
    //         $category->referred_video = json_decode($category->referred_video, true) ?? [];

    //         // Set response data
    //         $data['category_name'] = $language === 'chinese' ? $category->category_name_chinese : $category->category_name;
    //         $data['category_description'] = $language === 'chinese' ? $category->description_chinese : $category->description;
    //         $data['referred_video'] = $category->referred_video;
    //     } else {
    //         $data['category_name'] = null;
    //         $data['category_description'] = null;
    //         $data['referred_video'] = [];
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'data' => $data
    //     ]);
    // }
    public function quizCompletionContent(Request $request)
    {
        $language = $request->language ?? 'english';
        $category_id = $request->quiz_category_id;
        $userId = auth()->user()->id;

        $data = [];

        // ✅ Always use best marks per question (latest refreshed attempts considered)
        $data['attempt_quizz_marks'] = UserAttemptQuiz::where('user_id', $userId)
            ->where('quiz_category_id', $category_id)
            ->groupBy('question_id')
            ->selectRaw('MAX(marks_obtained) as best_marks')
            ->get()
            ->sum('best_marks');

        // Fetch category
        $category = QuizCategory::find($category_id);

        if ($category) {
            $category->referred_video = json_decode($category->referred_video, true) ?? [];

            $data['category_name'] = $language === 'chinese'
                ? $category->category_name_chinese
                : $category->category_name;

            $data['category_description'] = $language === 'chinese'
                ? $category->description_chinese
                : $category->description;

            $data['referred_video'] = $category->referred_video;
        } else {
            $data['category_name'] = null;
            $data['category_description'] = null;
            $data['referred_video'] = [];
        }

        return ApiResponse::success($data, 'Data fetched successfully!', 200);
    }
}
