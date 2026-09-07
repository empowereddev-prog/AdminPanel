<?php

namespace App\Http\Controllers\Admin;

use Auth;

use App\Http\Controllers\Controller;
use App\Models\Child;
use Illuminate\Http\Request;
use App\Models\NewsletterSubscriber;
use Yajra\DataTables\DataTables;
use Validator;
use App\Models\User;
use App\Models\PermissionUser;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;
use App\Models\Category;
use App\Models\TempUser;
use App\Models\Country;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizQuestion;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Hash;
use Illuminate\Support\Facades\Crypt;
use App\Models\Subscription;
use App\Models\UserAttemptQuiz;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{

    // public function __construct()
    // {
    //     $this->middleware(['auth:admin', 'checkActive']);
    // }

    private $user_management = 2;
    private $subadmin_menu_id = 2;
    // Method to display the user table view
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.planManagement.index')->with($data);
    }

    public function getUsers(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $query = User::where('user_role_id', '3')
                ->whereNotNull('email_verified_at')
                ->where('is_mobile_verified', 'yes')
                ->whereNull('school_id')
                ->where('deleted_at', null);

            // Apply order logic from DataTables
            if ($request->has('order')) {
                $columnIndex = $request->order[0]['column']; // Column index
                $columnName = $request->columns[$columnIndex]['data']; // Column name from request
                $sortDirection = $request->order[0]['dir']; // asc or desc

                // Only apply order if the column name exists in the database
                if (in_array($columnName, ['name', 'email', 'phone_no', 'status', 'created_at'])) {
                    $query->orderBy($columnName, $sortDirection);
                } else {
                    $query->latest(); // fallback
                }
            } else {
                $query->latest(); // default order
            }

            // Fetch results
            $users = $query->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($users)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("user/" . $row->id . "/subscription") . '" title="View Subscription" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("user/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("delete-user/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('name', function ($row) {
                        $name = ucfirst($row->name);
                        $shortName = strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
                        return "<span class='plan {$row->name}'>" . $shortName . "</span>";
                    })
                    // ->editColumn('description', function ($row){
                    //     return html_entity_decode(strip_tags($row->description));
                    // })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->editColumn('phone_no', function ($row) {
                        return $row->country_code . ' ' . $row->phone_no;
                    })
                    // ->editColumn('annually_amount', function ($row){
                    //     return 'US$' . number_format($row->annually_amount, 2);
                    // })
                    // ->editColumn('discount', function ($row){
                    //     return $row->discount . '%';
                    // })
                    ->rawColumns(['action', 'name', 'phone_no', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($users)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("user/" . $row->id . "/subscription") . '" title="View Subscription" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye""></i></a>&nbsp;';
                        return $btn;
                    })
                    ->editColumn('name', function ($row) {
                        $name = ucfirst($row->name);
                        $shortName = strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
                        return "<span class='plan {$row->name}'>" . $shortName . "</span>";
                    })

                    // ->editColumn('description', function ($row){
                    //     return html_entity_decode(strip_tags($row->description));
                    // })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->editColumn('phone_no', function ($row) {
                        return $row->country_code . ' ' . $row->phone_no;
                    })
                    ->rawColumns(['action', 'name', 'phone_no', 'status'])
                    ->make(true);
            }
        }
    }

    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data['countrycode'] = Country::get();
            return view('admin.planManagement.create')->with($data);
        }
        return redirect('dashboard');
    }
    public function store(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                // 'name'        => 'required|string|min:3|max:50',

                'name' => [
                    'required',
                    'min:3',
                    'max:50',
                    function ($attribute, $value, $fail) {
                        if ($value != strip_tags($value)) {
                            $fail('HTML tags are not allowed in the  name.');
                        }
                    },
                ],

                'email'       => [
                    'required',
                    'email',
                    'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
                    function ($attribute, $value, $fail) {
                        if (User::where('email', $value)
                            ->where('user_role_id', 3)
                            ->whereNull('deleted_at') // ignore soft deleted users
                            ->exists()
                        ) {
                            $fail('The email has already been taken for this role.');
                        }
                    },
                ],
                'code' => 'required|string|max:10',
                'phone_no'    => [
                    'required',
                    'numeric',
                    'digits_between:8,15',
                    function ($attribute, $value, $fail) {
                        if (preg_match('/^(\d)\1+$/', $value)) {
                            $fail('The phone number cannot have all digits the same.');
                        }
                        if (User::where('phone_no', $value)
                            ->where('user_role_id', 3)
                            ->whereNull('deleted_at') // ignore soft deleted users
                            ->exists()
                        ) {
                            $fail('The phone number has already been taken for this role.');
                        }
                    },
                ],
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $password = 'User' . '@' . \Str::studly(\Str::random(4));
        $user = [
            'name' => $request->name,
            'email' => $request->email,
            'country_code' => $request->code,
            'phone_no' => $request->phone_no,
            'user_role_id' => 3,
            'user_type' => 'parent',
            'email_verified_at' => Carbon::now()->format('Y:m:d h:i:s'),
            'is_mobile_verified' => 'yes',
            'password' => Hash::make($password)
        ];
        $emailData = [
            'name' => $request->name,
            'email' => $request->email,
            'country_code' => $request->code,
            'phone_no' => $request->phone_no,
            'user_role_id' => 3,
            'user_type' => 'parent',
            'email_verified_at' => Carbon::now()->format('Y:m:d h:i:s'),
            'is_mobile_verified' => 'yes',
            'password' => $password
        ];
        User::create($user);
        ___mail_sender($request->email, 'signup_user', $emailData, 'english');
        return redirect('user')->with('success', 'User Added Successfully.');
    }
    // public function edit(string $id)
    // {
    //     $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //     if (!empty($pre) && $pre->is_modify == 'yes') {
    //         $data = User::where('id', $id)
    //             ->first();
    //         if ($data) {
    //             $children = User::where('parent_id', $id)->get();
    //             foreach ($children as $child) {
    //                 $child->age = now()->year - date('Y', strtotime($child->dob));
    //                 // Watch history and progress calculation per child
    //                 $watchHistories = UserContentWatchHistory::where('child_id', $child->id)->get();

    //                 $categoryProgress = [];
    //                 foreach ($watchHistories as $history) {
    //                     $video = VideoContent::find($history->video_content_id);
    //                     if (!$video || !$video->category_id) continue;

    //                     $categoryId = $video->category_id;
    //                     $categoryName = Category::where('id', $categoryId)->value('category_name');
    //                     if (!isset($categoryProgress[$categoryId])) {
    //                         $categoryProgress[$categoryId] = [
    //                             'category_name' => $categoryName,
    //                             'total_video_seconds' => 0,
    //                             'watched_seconds' => 0,
    //                             'percentage' => 0,
    //                         ];
    //                     }

    //                     // Convert video duration to seconds
    //                     $videoSeconds = $this->convertTimeToSeconds($video->video_duration);
    //                     $categoryProgress[$categoryId]['total_video_seconds'] += $videoSeconds;

    //                     // Convert watched duration to seconds
    //                     $watchedSeconds = $this->convertTimeToSeconds($history->last_watched_duration);
    //                     $categoryProgress[$categoryId]['watched_seconds'] += $watchedSeconds;
    //                 }

    //                 foreach ($categoryProgress as $catId => &$progress) {
    //                     if ($progress['total_video_seconds'] > 0) {
    //                         $progress['percentage'] = round(
    //                             ($progress['watched_seconds'] / $progress['total_video_seconds']) * 100,
    //                             2
    //                         );
    //                     }
    //                 }
    //                 unset($progress);

    //                 // Attach progress to child
    //                 $child->categoryProgress = $categoryProgress;
    //             }
    //         }

    //         return view('admin.planManagement.edit', compact('data', 'children'));
    //     }
    //     return redirect('dashboard');
    // }

    // private function convertTimeToSeconds($time)
    // {
    //     $parts = explode(':', $time);
    //     if (count($parts) === 2) {
    //         return ((int)$parts[0] * 60) + (int)$parts[1];
    //     } elseif (count($parts) === 3) {
    //         return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
    //     }
    //     return 0;
    // }

    public function edit(string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = User::where('id', $id)->first();
            if ($data) {
                $children = User::where('parent_id', $id)->get();
                $categories = Category::all();

                foreach ($children as $child) {
                    $child->age = now()->year - date('Y', strtotime($child->dob));
                    $categoryProgress = [];

                    foreach ($categories as $category) {
                        $categoryId = $category->id;
                        $categoryName = $category->category_name;

                        // Get all videos in this category for child
                        $videos = VideoContent::where('category_id', $categoryId)
                            ->whereIn('user_type', ['child', 'both']) // ✅ instead of just 'child'
                            ->get();

                        $videoIds = $videos->pluck('id')->toArray();

                        $totalVideoSeconds = $videos->sum(function ($video) {
                            return $this->convertTimeToSeconds($video->video_duration);
                        });

                        $watchedDurations = UserContentWatchHistory::where('child_id', $child->id)
                            ->whereIn('video_content_id', $videoIds)
                            ->pluck('last_watched_duration')
                            ->toArray();

                        $watchedSeconds = array_sum(array_map([$this, 'convertTimeToSeconds'], $watchedDurations));

                        // $percentage = $totalVideoSeconds > 0
                        //     ? round(($watchedSeconds / $totalVideoSeconds) * 100, 2)
                        //     : 0;
                        $percentage = $totalVideoSeconds > 0
                            ? min(100, round(($watchedSeconds / $totalVideoSeconds) * 100, 2))
                            : 0;

                        $categoryProgress[$categoryId] = [
                            'category_name' => $categoryName,
                            'total_video_seconds' => $totalVideoSeconds,
                            'watched_seconds' => $watchedSeconds,
                            'percentage' => $percentage,
                            'total_video_duration' => gmdate("H:i:s", $totalVideoSeconds),
                        ];
                    }

                    // ---------- Quiz Category Progress ----------
                    $quizCategories = QuizCategory::where('status', 'active')
                        ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
                        ->get();

                    $quizProgress = [];
                    foreach ($quizCategories as $quizCategory) {
                        $quizIds = Quiz::where('quiz_category_id', $quizCategory->id)->pluck('id');

                        $totalMarks = QuizQuestion::whereIn('quiz_id', $quizIds)
                            ->where('status', 'active')
                            ->sum('marks');

                        $obtainedMarks = UserAttemptQuiz::where('user_id', $child->id)
                            ->whereIn('quiz_id', $quizIds)
                            ->sum('marks_obtained');

                        $quizPercentage = $totalMarks > 0
                            ? round(($obtainedMarks / $totalMarks) * 100, 2)
                            : 0;

                        $quizProgress[$quizCategory->id] = [
                            'category_name' => $quizCategory->category_name,
                            'percentage' => $quizPercentage,
                            'obtained_marks' => $obtainedMarks,
                            'total_marks' => $totalMarks,
                        ];
                    }
                    $child->quizProgress = $quizProgress;

                    $child->categoryProgress = $categoryProgress;
                }
            }

            // ================= Parent Video Progress =================
            $parentCategoryProgress = [];

            $categories = Category::all();

            foreach ($categories as $category) {
                $categoryId = $category->id;
                $categoryName = $category->category_name;

                // Parent can watch videos marked as 'parent' or 'both'
                $videos = VideoContent::where('category_id', $categoryId)
                    ->whereIn('user_type', ['parent', 'both'])
                    ->get();

                $videoIds = $videos->pluck('id')->toArray();

                $totalVideoSeconds = $videos->sum(function ($video) {
                    return $this->convertTimeToSeconds($video->video_duration);
                });

                $watchedDurations = UserContentWatchHistory::where('child_id', $data->id)
                    ->whereIn('video_content_id', $videoIds)
                    ->pluck('last_watched_duration')
                    ->toArray();


                $watchedSeconds = array_sum(
                    array_map([$this, 'convertTimeToSeconds'], $watchedDurations)
                );

                $percentage = $totalVideoSeconds > 0
                    ? min(100, round(($watchedSeconds / $totalVideoSeconds) * 100, 2))
                    : 0;

                $parentCategoryProgress[$categoryId] = [
                    'category_name' => $categoryName,
                    'total_video_seconds' => $totalVideoSeconds,
                    'watched_seconds' => $watchedSeconds,
                    'percentage' => $percentage,
                    'total_video_duration' => gmdate("H:i:s", $totalVideoSeconds),
                ];
            }

            $data->categoryProgress = $parentCategoryProgress;


            return view('admin.planManagement.edit', compact('data', 'children'));
        }

        return redirect('dashboard');
    }

    private function convertTimeToSeconds($time)
    {
        $parts = explode(':', $time);
        if (count($parts) === 2) {
            return ((int)$parts[0] * 60) + (int)$parts[1];
        } elseif (count($parts) === 3) {
            return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
        }
        return 0;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $minDob = Carbon::now()->subYears(11)->format('Y-m-d'); // Minimum date (11 years ago)
        $maxDob = Carbon::now()->subYears(24)->format('Y-m-d'); // Maximum date (24 years ago)
        $minDate = Carbon::now()->subYears(11)->format('d/m/Y');
        $maxDate = Carbon::now()->subYears(24)->format('d/m/Y');
        // Validate user details
        $validatedData = $request->validate([
            // 'name' => 'required|string|min:3|max:50',
            'name' => [
                'required',
                'min:3',
                'max:50',
                'not_regex:/<[^>]*>/u'
            ],
            'status' => 'required|in:active,inactive',
            // 'children.*.id' => 'nullable|exists:children,id',
            'children.*.id' => 'nullable',
            'children.*.name' => 'required|string|max:100|not_regex:/<[^>]*>/u',
            'children.*.dob' => "required|date|before_or_equal:$minDob|after_or_equal:$maxDob",
            'children.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            // 'children.*.loyalty_points' => 'required|numeric|min:0'
        ], [
            'children.*.dob.before_or_equal' => 'The date of birth must be before or equal to' . $minDate . '.',
            'children.*.dob.after_or_equal' => 'The date of birth must be after or equal to ' . $maxDate . '.',
            'children.*.name.required' => 'The child name is required.',
            'children.*.loyalty_points.required' => 'The loyalty points field is required.',
            'children.*.loyalty_points.min' => 'The loyalty points must not be less than 0.',
            'children.*.loyalty_points.numeric' => 'The loyalty points must be numeric value.',
        ]);

        // Update user details
        $user->update([
            'name' => $validatedData['name'],
            // 'email' => $validatedData['email'],
            // 'phone_no' => $validatedData['phone_no'],
            'status' => $validatedData['status'],
        ]);

        // Update children details
        if ($request->has('children')) {
            foreach ($request->children as $childData) {
                if (!empty($childData['id'])) {
                    $child = Child::find($childData['id']);
                    User::where('id', $childData['id'])->update([
                        'name' => $childData['name'],
                        'dob' => $childData['dob'],
                        'loyalty_points' => $childData['loyalty_points'] ?? 0,
                    ]);

                    if ($child) {
                        $child->name = $childData['name'];
                        $child->dob = $childData['dob'];
                        $child->loyalty_points = $childData['loyalty_points'];

                        // Handle Image Upload
                        if (isset($childData['image']) && $childData['image']->isValid()) {
                            $imagePath = $childData['image']->store('children_images', 'public');
                            $child->image = $imagePath;
                        }

                        $child->save();
                    }
                }
            }
        }

        return redirect()->route('user.index')->with('success', 'User updated successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     $userData = User::where('id', ($id))->first();

    //     User::where('parent_id',$userData->id)->delete();
    //     $userData->delete();
    //     return redirect()->back()->with('success', 'User Deleted Successfully.');
    // }

    public function destroy(string $id)
    {
        $userData = User::find($id);

        if (!$userData) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $emailData = [
            'name'  => $userData->name,
            'email' => $userData->email,
        ];

        // Send deletion email
        ___mail_sender($userData->email, 'delete_user_account', $emailData, 'english');

        // Delete all child users if any
        User::where('parent_id', $userData->id)->delete();

        // Delete the user (soft or hard depending on model)
        $userData->delete();

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function verifyUser($user_id, $email)
    {
        $decryptedEmail = Crypt::decryptString($email);
        // dd($user_id);
        $user = User::find($user_id);
        if (!$user) {
            return view('verifySignUp')->with('error', 'Invalid verification link.');

            // }
            // if($user->send_mail_timestamp < now()) {
            //     return view('verifySignUp')->with('message', 'This link has expired');

        }
        if ($user->email_verified_at) {
            if ($user->email_verified_at < now()) {
                return view('verifySignUp')->with('message', 'Your email is already verified.');
            }
        }
        // dd($user);
        $user->email = $decryptedEmail;
        $user->email_verified_at = now();
        $user->save();

        // dd($user);
        return view('verifySignUp')->with('success', 'Your email has been successfully verified!');
    }

    public function subscription(Request $request)
    {
        $data = Subscription::where('user_id', $request->id)->latest()->first();
        $user_details = User::where('id', $request->id)->first();
        return view('admin.user.subscribe', compact('data', 'user_details'));
    }

    public function deleteChild($id)
    {
        $child = User::find($id);

        if (!$child) {
            return response()->json([
                'status' => false,
                'message' => 'Child not found'
            ]);
        }

        $child->delete();

        return response()->json([
            'status' => true,
            'message' => 'Child deleted successfully'
        ]);
    }
}
