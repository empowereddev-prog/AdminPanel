<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mood;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Validator;
use Yajra\Datatables\datatables;

class SchoolController extends Controller
{
    private $subadmin_menu_id = 3;
    // Method to display the user table view
    public function index()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        return view('admin.schoolManagement.index')->with($data);
    }

    // public function getSchools(Request $request)
    // {
    //     $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
    //     if (!isset($pre) || empty($pre)) {
    //         return redirect('dashboard');
    //     }
    //     if ($request->ajax()) {
    //         $query = School::select('*')->withCount('students');

    //         // Apply order logic from DataTables request
    //         if ($request->has('order')) {
    //             $columnIndex = $request->order[0]['column'];
    //             $columnName = $request->columns[$columnIndex]['data'];
    //             $sortDirection = $request->order[0]['dir'];

    //             // Allow ordering on specific fields only
    //             $orderableColumns = ['name', 'no_of_parent', 'status']; // child_count not directly sortable in DB
    //             if (in_array($columnName, $orderableColumns)) {
    //                 $query->orderBy($columnName, $sortDirection);
    //             } else {
    //                 $query->latest();
    //             }
    //         } else {
    //             $query->latest();
    //         }

    //         $schools = $query->get();
    //         if (!empty($pre) && $pre->is_modify == 'yes') {
    //             return DataTables::of($schools)
    //                 ->addIndexColumn()
    //                 ->addColumn('action', function ($row) {
    //                     $btn = "";
    //                     $btn .= '<a href="' . url("school/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
    //                     $btn .= '<a href="' . url("view-school-details/" . $row->id) . '" class="view" title="View"  style="margin-left:5px;font-size:20px"><span class="mdi mdi-eye"></span></a>&nbsp;';
    //                     $btn .= '<a href="' . url("delete-school/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
    //                     return $btn;
    //                 })
    //                 ->addColumn('child_count', function ($row) {
    //                     return $row->students_count; // Laravel automatically provides this count
    //                 })
    //                 ->editColumn('name', function ($row) {
    //                     return "<span class='plan $row->name'>" . ucfirst($row->name) . "</span>";
    //                 })
    //                 ->editColumn('school_code', function ($row) {
    //                     return "<span class='plan $row->school_code'>" . $row->school_code . "</span>";
    //                 })
    //                 ->editColumn('status', function ($row) {
    //                     return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
    //                 })

    //                 ->rawColumns(['action', 'name', 'school_code', 'status'])
    //                 ->make(true);
    //         } else {
    //             return Datatables::of($schools)
    //                 ->addIndexColumn()
    //                 ->addColumn('child_count', function ($row) {
    //                     return $row->students_count; // Pass child count even for read-only users
    //                 })
    //                 ->make(true);
    //         }
    //     }
    // }

    public function getSchools(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            $query = School::select('*')
                ->withCount(['students as students_count' => function($q) {
                    $q->where('user_role_id', 3);
                }])
                ->withCount(['students as staff_count' => function($q) {
                    $q->where('user_role_id', 5);
                }]);

            // Apply order logic from DataTables request
            if ($request->has('order')) {
                $columnIndex = $request->order[0]['column'];
                $columnName = $request->columns[$columnIndex]['data'];
                $sortDirection = $request->order[0]['dir'];

                // Allow ordering on specific fields only
                $orderableColumns = ['name', 'no_of_parent', 'status'];
                if (in_array($columnName, $orderableColumns)) {
                    $query->orderBy($columnName, $sortDirection);
                } else {
                    $query->latest();
                }
            } else {
                $query->latest();
            }

            $schools = $query->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($schools)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("school/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                        $btn .= '<a href="' . url("view-school-details/" . $row->id) . '" class="view" title="View"  style="margin-left:5px;font-size:20px"><span class="mdi mdi-eye"></span></a>&nbsp;';
                        $btn .= '<a href="' . url("delete-school/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                    })
                    ->addColumn('child_count', function ($row) {
                        return $row->students_count;
                    })
                    // ✅ Added Column Mapping for Datatable View output
                    ->addColumn('staff_count', function ($row) {
                        return $row->staff_count;
                    })
                    ->editColumn('name', function ($row) {
                        return "<span class='plan $row->name'>" . ucfirst($row->name) . "</span>";
                    })
                    ->editColumn('school_code', function ($row) {
                        return "<span class='plan $row->school_code'>" . $row->school_code . "</span>";
                    })
                    ->editColumn('status', function ($row) {
                        return "<span class='sts $row->status'>" . ucfirst($row->status) . "</span>";
                    })
                    ->rawColumns(['action', 'name', 'school_code', 'status'])
                    ->make(true);
            } else {
                return Datatables::of($schools)
                    ->addIndexColumn()
                    ->addColumn('child_count', function ($row) {
                        return $row->students_count;
                    })
                    // ✅ Added Column Mapping for Read-Only Datatable View output
                    ->addColumn('staff_count', function ($row) {
                        return $row->staff_count;
                    })
                    ->make(true);
            }
        }
    }

    public function create()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            // Generate a unique school code (e.g., SCH-123456)
            $schoolCode = 'SCH-' . rand(100000, 999999);

            // Pass the generated school code to the view
            return view('admin.schoolManagement.add', compact('schoolCode'));
        }
        return redirect('dashboard');
    }
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         // 'school_name' => 'required|min:3|max:100|unique:schools,name',
    //         'school_name' => [
    //             'required',
    //             'min:3',
    //             'max:100',
    //             'unique:schools,name',
    //             'not_regex:/<[^>]*>/u'
    //         ],

    //         'school_code' => 'required|max:50|unique:schools,school_code',
    //         'max_limit' => 'required|numeric|min:1|max:100000000',
    //         'student_excel' => 'nullable|file|mimes:xlsx,xls',
    //         'subscription_type' => 'required',
    //         'email'       => [
    //             'required',
    //             'email',
    //             'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
    //             function ($attribute, $value, $fail) {
    //                 if (\App\Models\User::where('email', $value)->whereIn('user_role_id', [1, 2, 3, 4])->exists()) {
    //                     $fail('The email has already been taken for this role.');
    //                 }
    //             },
    //         ],
    //     ]);
    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }
    //     $school = [
    //         'name' => $request->school_name,
    //         'school_code' => $request->school_code,
    //         'status' => 'active',
    //         'max_limit' => $request->max_limit,
    //         'user_id' => auth()->user()->id,
    //         'subscription_type' => $request->subscription_type,
    //         'email' => $request->email
    //     ];
    //     $school = School::create($school);
    //     $school_data = School::latest()->first();
    //     if ($request->hasFile('student_excel')) {
    //         $file = $request->file('student_excel');
    //         $filePath = $file->storeAs('uploads', $school_data->id . '_' . $file->getClientOriginalName());
    //         $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
    //         $sheet = $spreadsheet->getActiveSheet();
    //         $data = $sheet->toArray();
    //         // Validate header
    //         $header = array_shift($data);
    //         $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number'];
    //         $header = array_slice($header, 0, 4);
    //         if ($header !== $expectedHeader) {
    //             return back()->with('error', 'Invalid file format. Please use the provided sample.');
    //         }

    //         $validData = [];

    //         foreach ($data as $row) {
    //             if (array_filter($row) && !empty($row[0]) && !empty($row[1]) && !empty($row[2]) && !empty($row[3])) {
    //                 // Validate phone number (must not start with 0, and must not be all zeros)
    //                 if (preg_match('/^0/', $row[3]) || preg_match('/^0+$/', $row[3]) || !preg_match('/^\d{8,15}$/', $row[3])) {
    //                     continue; // Skip invalid phone numbers
    //                 }

    //                 // Validate country code (must start with + and contain only numbers after)
    //                 if (!preg_match('/^\+\d+$/', $row[2])) {
    //                     continue; // Skip invalid country codes
    //                 }
    //                 // ✅ Validate email format & uniqueness across all schools
    //                 if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
    //                     continue; // Skip invalid or duplicate emails
    //                 }

    //                 // Check if the user already exists (prevent duplicates)
    //                 $existingUser = User::where('email', $row[1])
    //                     ->where('phone_no', $row[3])
    //                     ->where('school_id', $school->id)
    //                     ->exists();
    //                 if ($existingUser) {
    //                     continue; // Skip duplicate data
    //                 }

    //                 // ✅ Ensure no school can have the same email
    //                 $existingSchoolUser = User::where('email', $row[1])->where('user_role_id', '3')->exists();
    //                 if ($existingSchoolUser) {
    //                     continue; // Skip duplicate email globally
    //                 }

    //                 $validData[] = [
    //                     'name' => $row[0],
    //                     'email' => $row[1],
    //                     'country_code' => $row[2],
    //                     'phone_number' => $row[3],
    //                 ];
    //             }
    //         }
    //         if (empty($validData)) {
    //             School::where('id', $school_data->id)->delete();
    //             return back()->with('error', 'The excel file data is invalid.');
    //         }

    //         // Check existing students
    //         $existingStudents = User::where('school_id', $school->id)->count();
    //         $remainingLimit = $request->max_limit - $existingStudents;

    //         if ($remainingLimit <= 0) {
    //             return back()->with('error', 'You have already reached the max limit of ' . $request->max_limit . ' students.');
    //         }

    //         $insertedCount = 0;

    //         foreach ($validData as $student) {
    //             if ($insertedCount >= $remainingLimit) {
    //                 break;
    //             }

    //             $password = 'Sch' . \Str::studly(\Str::random(4) . '@1');
    //             $newUser = User::create([
    //                 'name' => $student['name'],
    //                 'email' => $student['email'],
    //                 'country_code' => $student['country_code'],
    //                 'phone_no' => $student['phone_number'],
    //                 'school_id' => $school->id,
    //                 'user_role_id' => 3,
    //                 'user_type' => 'parent',
    //                 'email_verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
    //                 'is_mobile_verified' => 'yes',
    //                 'password' => Hash::make($password)
    //             ]);

    //             $startDate = Carbon::now();

    //             // Determine end date based on subscription_type
    //             $endDate = match ($request->subscription_type) {
    //                 'monthly'   => $startDate->copy()->addMonth(),
    //                 'quarterly' => $startDate->copy()->addMonths(3),
    //                 'yearly'    => $startDate->copy()->addYear(),
    //                 default     => $startDate->copy()->addMonth(), // fallback
    //             };

    //             Subscription::create([
    //                 'user_id' => $newUser->id,
    //                 'subscription_type_id' => $request->subscription_type == 'monthly' ? 'com.empowered.monthly' : ($request->subscription_type == 'quarterly' ? 'com.empowered.quarterly' : 'com.empowered.yearly'),
    //                 'user_type' => 'parent',
    //                 'subscription_type' => $request->subscription_type,
    //                 'start_date' => $startDate->format('Y-m-d'),
    //                 'end_date' => $endDate->format('Y-m-d'),
    //                 'currency' => 'SGD',
    //                 'status' => 'Successful',
    //                 'price' => $request->subscription_type == 'monthly' ? '13.49' : ($request->subscription_type == 'quarterly' ? '33.81' : '101.63'),
    //             ]);

    //             $emailData = [
    //                 'email' => $student['email'],
    //                 'name' => $student['name'],
    //                 'password' => $password,
    //                 'school_code' => $school->school_code,
    //                 'school_name' => $school->name
    //             ];

    //             ___mail_sender($student['email'], 'signup_school_user', $emailData, 'english');
    //             $insertedCount++;
    //         }

    //         if ($insertedCount < count($validData)) {
    //             return redirect('school')->with('success', $insertedCount . ' students added successfully. Max limit reached, remaining students were ignored.');
    //         }
    //     }

    //     return redirect('school')->with('success', 'School Added Successfully.');
    // }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => [
                'required',
                'min:3',
                'max:100',
                'unique:schools,name',
                'not_regex:/<[^>]*>/u'
            ],
            'school_code' => 'required|max:50|unique:schools,school_code',
            'max_limit' => 'required|numeric|min:1|max:100000000',
            'student_excel' => 'nullable|file|mimes:xlsx,xls',
            'subscription_type' => 'required',
            'email' => [
                'required',
                'email',
                'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
                function ($attribute, $value, $fail) {
                    if (\App\Models\User::where('email', $value)->whereIn('user_role_id', [1, 2, 3, 4])->exists()) {
                        $fail('The email has already been taken for this role.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $school = [
            'name' => $request->school_name,
            'school_code' => $request->school_code,
            'status' => 'active',
            'max_limit' => $request->max_limit,
            'user_id' => auth()->user()->id,
            'subscription_type' => $request->subscription_type,
            'email' => $request->email
        ];
        $school = School::create($school);
        $school_data = School::latest()->first();

        if ($request->hasFile('student_excel')) {
            $file = $request->file('student_excel');
            $filePath = $file->storeAs('uploads', $school_data->id . '_' . $file->getClientOriginalName());
            $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $header = array_shift($data);
            $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number'];
            $header = array_slice($header, 0, 4);

            if ($header !== $expectedHeader) {
                return back()->with('error', 'Invalid file format. Please use the provided sample.')->withInput();
            }

            $validData = [];

            foreach ($data as $row) {
                if (array_filter($row) && !empty($row[0]) && !empty($row[1]) && !empty($row[2]) && !empty($row[3])) {
                    if (preg_match('/^0/', $row[3]) || preg_match('/^0+$/', $row[3]) || !preg_match('/^\d{8,15}$/', $row[3])) {
                        continue;
                    }

                    if (!preg_match('/^\+\d+$/', $row[2])) {
                        continue;
                    }

                    if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    // ✅ Check for duplicate in this school
                    $existingUser = User::where('email', $row[1])
                        ->where('phone_no', $row[3])
                        // ->where('school_id', $school->id)
                        ->exists();

                    if ($existingUser) {
                        return back()->with('error', 'Duplicate found: ' . $row[1] . ' with phone ' . $row[3] . ' already exists in this school.')->withInput();
                    }

                    // Check for same email in any other school as parent
                    $existingSchoolUser = User::where('email', $row[1])
                        ->where('user_role_id', 3)
                        ->exists();

                    if ($existingSchoolUser) {
                        continue;
                    }

                    $validData[] = [
                        'name' => $row[0],
                        'email' => $row[1],
                        'country_code' => $row[2],
                        'phone_number' => $row[3],
                    ];
                }
            }

            if (empty($validData)) {
                School::where('id', $school_data->id)->delete();
                return back()->with('error', 'The excel file data is invalid.')->withInput();
            }

            $existingStudents = User::where('school_id', $school->id)->count();
            $remainingLimit = $request->max_limit - $existingStudents;

            if ($remainingLimit <= 0) {
                return back()->with('error', 'You have already reached the max limit of ' . $request->max_limit . ' students.')->withInput();
            }

            // ✅ Keep only top rows as per remaining limit
            $validData = array_slice($validData, 0, $remainingLimit);

            $insertedCount = 0;

            foreach ($validData as $student) {
                $password = 'Sch' . \Str::studly(\Str::random(4) . '@1');

                $newUser = User::create([
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'country_code' => $student['country_code'],
                    'phone_no' => $student['phone_number'],
                    'school_id' => $school->id,
                    'user_role_id' => 3,
                    'user_type' => 'parent',
                    'email_verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'is_mobile_verified' => 'yes',
                    'password' => Hash::make($password)
                ]);

                $startDate = Carbon::now();
                $endDate = match ($request->subscription_type) {
                    'monthly'   => $startDate->copy()->addMonth(),
                    'quarterly' => $startDate->copy()->addMonths(3),
                    'yearly'    => $startDate->copy()->addYear(),
                    default     => $startDate->copy()->addMonth(),
                };

                Subscription::create([
                    'user_id' => $newUser->id,
                    'subscription_type_id' => $request->subscription_type == 'monthly' ? 'com.empowered.monthly' : ($request->subscription_type == 'quarterly' ? 'com.empowered.quarterly' : 'com.empowered.yearly'),
                    'user_type' => 'parent',
                    'subscription_type' => $request->subscription_type,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'currency' => 'SGD',
                    'status' => 'Successful',
                    'price' => $request->subscription_type == 'monthly' ? '13.49' : ($request->subscription_type == 'quarterly' ? '33.81' : '101.63'),
                ]);

                $emailData = [
                    'email' => $student['email'],
                    'name' => $student['name'],
                    'password' => $password,
                    'school_code' => $school->school_code,
                    'school_name' => $school->name
                ];

                ___mail_sender($student['email'], 'signup_school_user', $emailData, 'english');
                $insertedCount++;
            }

            return redirect('school')->with('success', $insertedCount . ' students added successfully.');
        }
        return redirect('school')->with('success', 'School added successfully.');
    }




    public function show($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $school = School::with('students')->findOrFail($id);
            return view('admin.schoolManagement.view', compact('school'));
        }
        return redirect('dashboard');
    }


    // public function exportSchoolUsers(Request $request, $schoolId)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date'   => 'required|date|after_or_equal:start_date',
    //     ]);

    //     $school = School::findOrFail($schoolId);

    //     $users = User::where('school_id', $schoolId)
    //         ->whereBetween('created_at', [
    //             $request->start_date . ' 00:00:00',
    //             $request->end_date . ' 23:59:59'
    //         ])
    //         ->where('user_role_id', 3) // school parents
    //         ->get();

    //     if ($users->isEmpty()) {
    //         return back()->with('error', 'No users found for selected date range.');
    //     }

    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();

    //     // 🔹 Headers
    //     $sheet->setCellValue('A1', 'Name');
    //     $sheet->setCellValue('B1', 'Email');
    //     $sheet->setCellValue('C1', 'Phone');
    //     $sheet->setCellValue('D1', 'Status');
    //     $sheet->setCellValue('E1', 'Created Date');

    //     // 🔹 Data
    //     $row = 2;
    //     foreach ($users as $user) {
    //         $sheet->setCellValue('A' . $row, $user->name);
    //         $sheet->setCellValue('B' . $row, $user->email);
    //         $sheet->setCellValue('C' . $row, $user->country_code . ' ' . $user->phone_no);
    //         $sheet->setCellValue('D' . $row, ucfirst($user->status));
    //         $sheet->setCellValue('E' . $row, $user->created_at->format('Y-m-d'));
    //         $row++;
    //     }

    //     foreach (range('A', 'E') as $col) {
    //         $sheet->getColumnDimension($col)->setAutoSize(true);
    //     }

    //     $fileName = 'school_users_' . now()->format('Ymd_His') . '.xlsx';

    //     $writer = new Xlsx($spreadsheet);

    //     return new StreamedResponse(function () use ($writer) {
    //         $writer->save('php://output');
    //     }, 200, [
    //         'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //         'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
    //         'Cache-Control'       => 'max-age=0',
    //     ]);
    // }

    public function exportSchoolUsers(Request $request, $schoolId)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'role_type'  => 'nullable|in:all,parent,teacher'
        ]);

        $school = School::findOrFail($schoolId);

        $query = User::where('school_id', $schoolId);

        $roleType = $request->input('role_type', 'all');
        if ($roleType === 'parent') {
            $query->where('user_role_id', 3);
        } elseif ($roleType === 'teacher') {
            $query->where('user_role_id', 5);
        } else {
            $query->whereIn('user_role_id', [3, 5]);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'No users found matching the selected criteria.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Country Code');
        $sheet->setCellValue('D1', 'Phone Number');
        $sheet->setCellValue('E1', 'Username');
        $sheet->setCellValue('F1', 'Role');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Created Date');

        $row = 2;
        foreach ($users as $user) {
            $roleLabel = ($user->user_role_id == 5) ? 'Staff / Teacher' : 'Parent';

            $sheet->setCellValue('A' . $row, $user->name);
            $sheet->setCellValue('B' . $row, $user->email);

            $sheet->setCellValueExplicit('C' . $row, $user->country_code ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, $user->phone_no ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

            $sheet->setCellValue('E' . $row, $user->username ?? '');
            $sheet->setCellValue('F' . $row, $roleLabel);
            $sheet->setCellValue('G' . $row, ucfirst($user->status));
            $sheet->setCellValue('H' . $row, $user->created_at->format('Y-m-d'));
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'school_users_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }


    public function edit(string $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = School::where('id', $id)->first();
            return view('admin.schoolManagement.edit', compact('data'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         // 'school_name' => 'required|min:3|max:100',
    //         'school_name' => [
    //             'required',
    //             'min:3',
    //             'max:100',
    //             'unique:schools,name,' . $id . ',id',
    //             'not_regex:/<[^>]*>/u'

    //         ],
    //         'max_limit' => 'required|numeric|min:1|max:100000000',
    //         'status' => 'required|in:active,inactive',
    //         'student_excel' => 'nullable|file|mimes:xlsx,xls',
    //         'subscription_type' => 'required'
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     $school = School::findOrFail($id);

    //     $school->update([
    //         'name' => $request->school_name,
    //         'school_code' => $request->school_code,
    //         'status' => $request->status,
    //         'max_limit' => $request->max_limit,
    //         'subscription_type' => $request->subscription_type
    //     ]);

    //     if ($request->hasFile('student_excel')) {
    //         $file = $request->file('student_excel');
    //         $fileName = $id . '_' . $file->getClientOriginalName();
    //         $filePath = 'uploads/' . $fileName;

    //         // Delete old file if exists
    //         if (Storage::exists($filePath)) {
    //             Storage::delete($filePath);
    //         }
    //         $file->storeAs('uploads', $fileName);

    //         $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
    //         $sheet = $spreadsheet->getActiveSheet();
    //         $data = $sheet->toArray();

    //         // Validate header
    //         $header = array_shift($data);
    //         $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number'];
    //         $header = array_slice($header, 0, 4);

    //         if ($header !== $expectedHeader) {
    //             return back()->with('error', 'Invalid file format. Please use the provided sample.');
    //         }

    //         $validData = [];

    //         foreach ($data as $row) {
    //             if (array_filter($row) && !empty($row[0]) && !empty($row[1]) && !empty($row[2]) && !empty($row[3])) {
    //                 // Validate phone number (must not start with 0, and must not be all zeros)
    //                 if (preg_match('/^0/', $row[3]) || preg_match('/^0+$/', $row[3]) || !preg_match('/^\d{8,15}$/', $row[3])) {
    //                     continue; // Skip invalid phone numbers
    //                 }


    //                 // Validate country code (must start with + and contain only numbers after)
    //                 if (!preg_match('/^\+\d+$/', $row[2])) {
    //                     continue; // Skip invalid country codes
    //                 }
    //                 // ✅ Validate email format & uniqueness across all schools
    //                 if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
    //                     continue; // Skip invalid or duplicate emails
    //                 }

    //                 // Check if the user already exists (prevent duplicates)
    //                 $existingUser = User::where('email', $row[1])
    //                     ->where('phone_no', $row[3])
    //                     ->where('school_id', $school->id)
    //                     ->exists();
    //                 if ($existingUser) {
    //                     continue; // Skip duplicate data
    //                 }


    //                 // ✅ Ensure no school can have the same email
    //                 $existingSchoolUser = User::where('email', $row[1])->where('user_role_id', '3')->exists();
    //                 if ($existingSchoolUser) {
    //                     continue; // Skip duplicate email globally
    //                 }


    //                 $validData[] = [
    //                     'name' => $row[0],
    //                     'email' => $row[1],
    //                     'country_code' => $row[2],
    //                     'phone_number' => $row[3],
    //                 ];
    //             }
    //         }

    //         if (empty($validData)) {
    //             return back()->with('error', 'The excel file data is invalid.');
    //         }

    //         // Check existing students
    //         $existingStudents = User::where('school_id', $school->id)->count();
    //         $remainingLimit = $request->max_limit - $existingStudents;

    //         if ($remainingLimit <= 0) {
    //             return back()->with('error', 'You have already reached the max limit of ' . $request->max_limit . ' students.');
    //         }

    //         $insertedCount = 0;

    //         foreach ($validData as $student) {
    //             if ($insertedCount >= $remainingLimit) {
    //                 break;
    //             }

    //             $password = 'Sch' . \Str::studly(\Str::random(4) . '@1');
    //             $newUser =  User::create([
    //                 'name' => $student['name'],
    //                 'email' => $student['email'],
    //                 'country_code' => $student['country_code'],
    //                 'phone_no' => $student['phone_number'],
    //                 'school_id' => $school->id,
    //                 'user_role_id' => 3,
    //                 'user_type' => 'parent',
    //                 'email_verified_at' => Carbon::now()->format('Y:m:d h:i:s'),
    //                 'is_mobile_verified' => 'yes',
    //                 'password' => Hash::make($password)
    //             ]);
    //             $startDate = Carbon::now();

    //             // Determine end date based on subscription_type
    //             $endDate = match ($request->subscription_type) {
    //                 'monthly'   => $startDate->copy()->addMonth(),
    //                 'quarterly' => $startDate->copy()->addMonths(3),
    //                 'yearly'    => $startDate->copy()->addYear(),
    //                 default     => $startDate->copy()->addMonth(), // fallback
    //             };
    //             Subscription::create([
    //                 'user_id' => $newUser->id,
    //                 'subscription_type_id' => $request->subscription_type == 'monthly' ? 'com.empowered.monthly' : ($request->subscription_type == 'quarterly' ? 'com.empowered.quarterly' : 'com.empowered.yearly'),
    //                 'user_type' => 'parent',
    //                 'subscription_type' => $request->subscription_type,
    //                 'start_date' => $startDate->format('Y-m-d'),
    //                 'end_date' => $endDate->format('Y-m-d'),
    //                 'currency' => 'SGD',
    //                 'status' => 'Successful',
    //                 'price' => $request->subscription_type == 'monthly' ? '13.49' : ($request->subscription_type == 'quarterly' ? '33.81' : '101.63'),
    //             ]);

    //             $emailData = [
    //                 'email' => $student['email'],
    //                 'name' => $student['name'],
    //                 'password' => $password,
    //                 'school_code' => $school->school_code,
    //                 'school_name' => $school->name
    //             ];

    //             ___mail_sender($student['email'], 'signup_school_user', $emailData, 'english');
    //             $insertedCount++;
    //         }

    //         if ($insertedCount < count($validData)) {
    //             return redirect('school')->with('success', $insertedCount . ' students added successfully. Max limit reached, remaining students were ignored.');
    //         }
    //     }

    //     return redirect('school')->with('success', 'School Updated Successfully.');
    // }
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => [
                'required',
                'min:3',
                'max:100',
                'unique:schools,name,' . $id . ',id',
                'not_regex:/<[^>]*>/u'
            ],
            'school_code' => 'required|max:50|unique:schools,school_code,' . $id . ',id',
            'max_limit' => 'required|numeric|min:1|max:100000000',
            'status' => 'required|in:active,inactive',
            'student_excel' => 'nullable|file|mimes:xlsx,xls',
            'staff_excel' => 'nullable|file|mimes:xlsx,xls',
            'subscription_type' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $school = School::findOrFail($id);

        $school->update([
            'name' => $request->school_name,
            'school_code' => $request->school_code,
            'status' => $request->status,
            'max_limit' => $request->max_limit,
            'subscription_type' => $request->subscription_type
        ]);

        if ($request->status === 'inactive') {
            User::where('school_id', $school->id)->update(['status' => 'inactive']);
        }

        // ✅ Process Excel if uploaded
        if ($request->hasFile('student_excel')) {
            try {
                $file = $request->file('student_excel');
                $filePath = $file->storeAs('uploads', $id . '_' . $file->getClientOriginalName());
                $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();

                $header = array_slice(array_shift($data), 0, 4);
                $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number'];

                if ($header !== $expectedHeader) {
                    return back()->with('error', 'Invalid file format. Please use the provided sample.')->withInput();
                }

                $validData = [];

                foreach ($data as $row) {
                    if (!array_filter($row) || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                        continue;
                    }

                    if (preg_match('/^0/', $row[3]) || preg_match('/^0+$/', $row[3]) || !preg_match('/^\d{8,15}$/', $row[3])) {
                        continue;
                    }

                    if (!preg_match('/^\+\d+$/', $row[2])) {
                        continue;
                    }

                    if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    $existingUser = User::where('email', $row[1])
                        ->where('phone_no', $row[3])
                        ->where('school_id', $school->id)
                        ->exists();

                    if ($existingUser) {
                        return back()->with('error', 'Duplicate found: ' . $row[1] . ' with phone ' . $row[3] . ' already exists in this school.')->withInput();
                    }

                    $existingSchoolUser = User::where('email', $row[1])
                        ->where('user_role_id', 3)
                        ->exists();

                    if ($existingSchoolUser) {
                        continue;
                    }

                    $validData[] = [
                        'name' => $row[0],
                        'email' => $row[1],
                        'country_code' => $row[2],
                        'phone_number' => $row[3],
                    ];
                }

                if (empty($validData)) {
                    return back()->with('error', 'The Excel file data is invalid.')->withInput();
                }

                $existingStudents = User::where('school_id', $school->id)
                    ->where('user_role_id', 3)
                    ->count();

                $remainingLimit = $request->max_limit - $existingStudents;

                if ($remainingLimit <= 0) {
                    return back()->with('error', 'You have already reached the max limit of ' . $request->max_limit . ' students.')->withInput();
                }

                $validData = array_slice($validData, 0, $remainingLimit);
                $insertedCount = 0;

                foreach ($validData as $student) {
                    $password = 'Sch' . \Str::studly(\Str::random(4) . '@1');

                    $newUser = User::create([
                        'name' => $student['name'],
                        'email' => $student['email'],
                        'country_code' => $student['country_code'],
                        'phone_no' => $student['phone_number'],
                        'school_id' => $school->id,
                        'user_role_id' => 3,
                        'user_type' => 'parent',
                        'email_verified_at' => now(),
                        'is_mobile_verified' => 'yes',
                        'password' => Hash::make($password)
                    ]);

                    $startDate = now();
                    $endDate = match ($request->subscription_type) {
                        'monthly' => $startDate->copy()->addMonth(),
                        'quarterly' => $startDate->copy()->addMonths(3),
                        'yearly' => $startDate->copy()->addYear(),
                        default => $startDate->copy()->addMonth(),
                    };

                    Subscription::create([
                        'user_id' => $newUser->id,
                        'subscription_type_id' => match ($request->subscription_type) {
                            'monthly' => 'com.empowered.monthly',
                            'quarterly' => 'com.empowered.quarterly',
                            'yearly' => 'com.empowered.yearly',
                        },
                        'user_type' => 'parent',
                        'subscription_type' => $request->subscription_type,
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'currency' => 'SGD',
                        'status' => 'Successful',
                        'price' => match ($request->subscription_type) {
                            'monthly' => '13.49',
                            'quarterly' => '33.81',
                            'yearly' => '101.63',
                        },
                    ]);

                    ___mail_sender($student['email'], 'signup_school_user', [
                        'email' => $student['email'],
                        'name' => $student['name'],
                        'password' => $password,
                        'school_code' => $school->school_code,
                        'school_name' => $school->name
                    ], 'english');

                    $insertedCount++;
                }

                return redirect('school')->with('success', $insertedCount . ' students added successfully.');
            } catch (\Throwable $e) {
                return back()->with('error', 'Something went wrong while processing the Excel file.')->withInput();
            }
        }

        // NEW PROCESSING ENGINE: Parse and Import Staff Details Excel File
        // if ($request->hasFile('staff_excel')) {
        //     try {
        //         $file = $request->file('staff_excel');
        //         $filePath = $file->storeAs('uploads', $id . '_staff_' . $file->getClientOriginalName());
        //         $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
        //         $sheet = $spreadsheet->getActiveSheet();
        //         $data = $sheet->toArray();

        //         $header = array_slice(array_shift($data), 0, 5);
        //         $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number', 'Username'];

        //         if ($header !== $expectedHeader) {
        //             return back()->with('error', 'Invalid staff file format. Please use the provided staff sample.')->withInput();
        //         }

        //         $insertedStaffCount = 0;

        //         foreach ($data as $row) {
        //             if (!array_filter($row) || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
        //                 continue;
        //             }

        //             if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
        //                 continue;
        //             }

        //             $existingGlobalUser = User::where('email', $row[1])->exists();
        //             if ($existingGlobalUser) {
        //                 continue;
        //             }

        //             $existingUsername = User::where('username', $row[4])->exists();
        //             if ($existingUsername) {
        //                 continue;
        //             }

        //             $password = 'Tch' . \Str::studly(\Str::random(4) . '@2');

        //             $newTeacher = User::create([
        //                 'name' => $row[0],
        //                 'email' => $row[1],
        //                 'country_code' => $row[2],
        //                 'phone_no' => $row[3],
        //                 'username' => $row[4],
        //                 'school_id' => $school->id,
        //                 'user_role_id' => 5,
        //                 'user_type' => 'teacher',
        //                 'email_verified_at' => now(),
        //                 'is_mobile_verified' => 'yes',
        //                 'password' => Hash::make($password)
        //             ]);

        //             $emailData = [
        //                 'name'     => $row[0],
        //                 'username' => $row[4],
        //                 'password' => $password
        //             ];

        //             ___mail_sender($row[1], 'signup_teacher', $emailData, 'english');

        //             $insertedStaffCount++;
        //         }

        //         if ($insertedStaffCount === 0) {
        //             return back()
        //                 ->with('error', 'The Staff/Teacher Excel file data or format is invalid.')
        //                 ->withInput();
        //         }

        //         return redirect('school')->with('success', $insertedStaffCount . ' staff members added successfully.');

        //     } catch (\Throwable $e) {
        //         return back()->with('error', 'Staff Error: ' . $e->getMessage())->withInput();
        //     }
        // }

        // NEW PROCESSING ENGINE: Parse and Import Staff Details Excel File
        if ($request->hasFile('staff_excel')) {
            try {
                $file = $request->file('staff_excel');
                $filePath = $file->storeAs('uploads', $id . '_staff_' . $file->getClientOriginalName());
                $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();
                $header = array_slice(array_shift($data), 0, 5);
                $expectedHeader = ['Name', 'Email', 'Country Code', 'Phone Number', 'Username'];

                if ($header !== $expectedHeader) {
                    return back()->with('error', 'Invalid staff file format. Please use the exact staff import layout structure.')->withInput();
                }

                $insertedStaffCount = 0;
                $rowNumber = 1;

                foreach ($data as $row) {
                    $rowNumber++;

                    if (!array_filter($row)) {
                        continue;
                    }

                    if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                        return back()->with('error', "Row {$rowNumber}: All fields (Name, Email, Country Code, Phone Number, Username) are required.") ->withInput();
                    }

                    if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
                        return back()->with('error', "Row {$rowNumber}: '{$row[1]}' is not a valid email address.")->withInput();
                    }

                    $existingGlobalUser = User::where('email', $row[1])->exists();
                    if ($existingGlobalUser) {
                        continue;
                        // return back()->with('error', "Row {$rowNumber}: Duplicate Email found. '{$row[1]}' is already registered in the system.") ->withInput();
                    }

                    $existingUsername = User::where('username', $row[4])->exists();
                    if ($existingUsername) {
                        continue;
                        // return back()->with('error', "Row {$rowNumber}: Duplicate Username found. '{$row[4]}' is already taken.") ->withInput();
                    }

                    if (preg_match('/[^a-zA-Z0-9_\-\+]/', $row[4])) {
                        return back()->with('error', "Row {$rowNumber}: Invalid username format for '{$row[4]}'. Use only letters, numbers, underscores, dashes, or plus signs.") ->withInput();
                    }

                    $password = 'Tch' . \Str::studly(\Str::random(4) . '@2');

                    $newTeacher = User::create([
                        'name' => $row[0],
                        'email' => $row[1],
                        'country_code' => $row[2],
                        'phone_no' => $row[3],
                        'username' => $row[4],
                        'school_id' => $school->id,
                        'user_role_id' => 5,
                        'user_type' => 'teacher',
                        'email_verified_at' => now(),
                        'is_mobile_verified' => 'yes',
                        'password' => Hash::make($password)
                    ]);

                    $emailData = [
                        'name'     => $row[0],
                        'username' => $row[4],
                        'password' => $password
                    ];

                    ___mail_sender($row[1], 'signup_teacher', $emailData, 'english');

                    $insertedStaffCount++;
                }

                if ($insertedStaffCount === 0) {
                    return back()->with('error', 'The Excel file contains no valid staff records to process.')->withInput();
                }

                return redirect('school')->with('success', $insertedStaffCount . ' staff members added successfully.');

            } catch (\Throwable $e) {
                return back()->with('error', 'Staff Parsing Exception: ' . $e->getMessage())->withInput();
            }
        }

        return redirect('school')->with('success', 'School updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     $schoolData = School::where('id', ($id))->first();
    //     if ($schoolData) {
    //         $school_user = User::where('school_id', $schoolData->id)->first();
    //         $child = User::where('parent_id', $school_user->id)->first();
    //         if ($school_user && $child) {
    //             User::where('parent_id', $school_user->id)->delete();
    //             User::where('school_id', $schoolData->id)->delete();
    //         }
    //         $schoolData->delete();
    //         return redirect()->back()->with('success', 'School Deleted Successfully.');
    //     }
    // }
    public function destroy(string $id)
    {
        $school = School::find($id);

        if (!$school) {
            return redirect()->back()->with('error', 'School not found.');
        }

        // Get all users linked to this school
        $schoolUsers = User::where('school_id', $school->id)->get();

        foreach ($schoolUsers as $schoolUser) {
            // Delete all children of this school user
            User::where('parent_id', $schoolUser->id)->delete();
        }

        // Delete all school users
        User::where('school_id', $school->id)->delete();

        // Delete the school
        $school->delete();

        return redirect()->back()->with('success', 'School deleted successfully.');
    }

    public function destroySchoolUser(string $id)
    {
        $school_user = User::where('id', $id)->first();

        if ($school_user) {
            $emailData = [
                'name'  => $school_user->name,
                'email' => $school_user->email,
            ];

            ___mail_sender($school_user->email, 'delete_user_account', $emailData, 'english');
            User::where('parent_id', $school_user->id)->delete();
            $school_user->delete();

            return redirect()->back()->with('success', 'School User Deleted Successfully.');
        }

        return redirect()->back()->with('error', 'School User not found.');
    }


    // public function verifyUser($user_id, $email)
    // {
    //     // dd($user_id);
    //     $user = User::find($user_id);
    //     if (!$user) {
    //         return view('verifySignUp')->with('error', 'Invalid verification link.');

    //     }
    //     if ($user->email_verified_at) {
    //         if ($user->email_verified_at < now()) {
    //             return view('verifySignUp')->with('message', 'Your email is already verified.');
    //         }
    //     }
    //     // dd($user);
    //     $user->email = $email;
    //     $user->email_verified_at = now();
    //     $user->save();
    //     // dd($user);
    //     return view('verifySignUp')->with('success', 'Your email has been successfully verified!');
    // }
    public function downloadSampleExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Headers
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Country Code');
        $sheet->setCellValue('D1', 'Phone Number');

        // Add Sample Data
        $sheet->setCellValue('A2', 'John Doe');
        $sheet->setCellValue('B2', 'johndoe@example.com');
        $sheet->setCellValueExplicit('C2', '+91', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', '7676767676', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('A3', 'Jane Smith');
        $sheet->setCellValue('B3', 'janesmith@example.com');
        $sheet->setCellValueExplicit('C3', '+65', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D3', '5454545454', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        foreach (range('A', 'D') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('D2:D3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $writer = new Xlsx($spreadsheet);

        $fileName = 'sample_students.xlsx';
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function moodsOverview(Request $request)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $schools = School::where('status', 'active')->get();

        $positiveMoodIds = Mood::where('type', 'positive')->pluck('id')->toArray();
        $negativeMoodIds = Mood::where('type', 'negative')->pluck('id')->toArray();

        $chartData = [];

        foreach ($schools as $school) {
            $parentIds = User::where('school_id', $school->id)
                ->where('user_type', 'parent')
                ->pluck('id');

            $childIds = User::whereIn('parent_id', $parentIds)
                ->where('user_type', 'child')
                ->pluck('id')
                ->toArray();

            $totalChildren = count($childIds);

            if ($totalChildren === 0) {
                $chartData[] = [
                    'school_name' => $school->name,
                    'positive'    => 0,
                    'negative'    => 0,
                ];
                continue;
            }

            $positiveQuery = DB::table('child_moods')
                ->whereIn('child_id', $childIds)
                ->whereIn('mood_id', $positiveMoodIds)
                ->whereNull('deleted_at');

            $negativeQuery = DB::table('child_moods')
                ->whereIn('child_id', $childIds)
                ->whereIn('mood_id', $negativeMoodIds)
                ->whereNull('deleted_at');

            if ($startDate && $endDate) {
                $positiveQuery->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
                $negativeQuery->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            }

            $positiveStudents = $positiveQuery->distinct('child_id')->count('child_id');
            $negativeStudents = $negativeQuery->distinct('child_id')->count('child_id');

            $chartData[] = [
                'school_name' => $school->name,
                'positive'    => $totalChildren > 0 ? round(($positiveStudents / $totalChildren) * 100, 2) : 0,
                'negative'    => $totalChildren > 0 ? round(($negativeStudents / $totalChildren) * 100, 2) : 0,
            ];
        }

        return view('admin.schoolManagement.moods_overview', [
            'chartData' => $chartData,
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'pre'       => $pre,
        ]);
    }

    public function downloadSampleStaffExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Country Code');
        $sheet->setCellValue('D1', 'Phone Number');
        $sheet->setCellValue('E1', 'Username');

        $sheet->setCellValue('A2', 'Teacher Alex');
        $sheet->setCellValue('B2', 'teacher.alex@example.com');
        $sheet->setCellValueExplicit('C2', '+65', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', '81234567', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E2', 'alex_teacher123');

        foreach (range('A', 'E') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        $sheet->getStyle('D2')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'sample_staff.xlsx';

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
