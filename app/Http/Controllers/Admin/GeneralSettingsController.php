<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use Yajra\Datatables\Datatables;
use App\Http\Requests\Admin\GeneralSettingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\PermissionUser;
use Auth;
use Carbon\Carbon;
use App\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;
use Validator;
use App\Models\Category;
use App\Models\School;
use App\Models\User;

class GeneralSettingsController extends Controller
{
    private $setting = 12;
    private $subadmin_menu_id = 12;

    public function editSystemSetting()
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre)) {
            $data['title'] = 'Settings';
            $data['setting'] = Setting::orderBy('id', 'DESC')->get();
            return view('admin.settings.edit')->with($data);
        }
        return redirect('dashboard');
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateSystemSetting(GeneralSettingRequest $request)
    {
        $settingArray = [
            'MAIL_MAILER',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'MAIL_PASSWORD',
            'TWILIO_SID',
            'TWILIO_TOKEN',
            'TWILIO_PHONE_NO'
        ];

        foreach ($settingArray as $fieldName) {
            $fieldVal = $request->$fieldName;
            $value = array('value' => $fieldVal);
            $where = array(
                'title' => $fieldName,
            );
            $settingObj = new Setting();
            $settingObj->updateSetting($where, $value);

            $this->updateEnvSetting($fieldName, $fieldVal);
        }
        return redirect(url('settings'))->with('success', 'Settings Updated Successfully!');
    }

    // Function to update the .env file
    private function updateEnvSetting($key, $value)
    {
        $path = base_path('.env');
        // dd($key);
        if (File::exists($path)) {
            // Get the content of the .env file
            $envContent = File::get($path);

            // Find if the key exists in the file
            $oldValue = env($key);
            //   dd($oldValue);
            // If the key already exists, replace the value, otherwise, append it
            if ($oldValue !== null) {
                // Replace the existing value with the new one
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            }

            // Write the new content to the .env file
            File::put($path, $envContent);

            // Clear the config cache
            Artisan::call('config:clear');
        }
    }

    //  public function uploadbuild(Request $request){
    //     $version = Setting::where('title','MacOS Latest Version')->value('value');
    //         $newVersion = $version + 1;

    //         Setting::where('title','MacOS Latest Version')->update([
    //             'value' => $newVersion
    //         ]);
    //         $data['title'] = 'Upload Build';
    //         return view('admin.upload-file.build-file')->with($data);
    //     }

    //     public function updateBuild(Request $request){

    //         $data['title'] = 'Upload Build';
    //         $image_name = $request->file('image');
    //          if(file_exists($image_name->getClientOriginalName())){
    //             @unlink(public_path('/build/'.$image_name->getClientOriginalName()));
    //         }
    //         $input['image'] = $image_name->getClientOriginalName();
    //         $destinationPath = public_path('/build');
    //         $image_name->move($destinationPath, $input['image']);
    //         return view('admin.upload-file.build-file')->with($data)->with('message', 'Build Uploaded Successfully!');
    //    }


    public function reportGenrate(Request $request, $key)
    {
        // dd($key);
        $data['key'] = ($key == 'VideoContent') ? 'Video Content' : (($key == 'KnowledgeSession') ? 'Knowledge Session' : $key);
        $now = Carbon::now();
        $data['weekStartDate'] = $now->startOfWeek()->format('d/m/Y');
        $data['todayDate'] = $now->today()->format('d/m/Y');
        $data['user_type'] = Role::whereIn('id', [3, 4])->get();
        if ($key == 'VideoContent' || $key == 'KnowledgeSession') {
            $data['category'] = Category::get();
        }
        return view('admin.report', $data);
    }

    public function downloadExcelFile(Request $request, $key)
    {
        // dd($key);
        if ($key == 'User') {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:roles,id', // checks if user_id is present and valid
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $user_id = $request->user_id;
            // Fetch data
            $users = User::when($user_id, function ($query) use ($user_id) {
                return $query->where('user_role_id', $user_id);
            })->select('id', 'name', 'email', 'created_at')->latest()->get();
            if ($users->isEmpty()) {
                return redirect()->back()->with('error', 'No data found for the selected user type.');
            }
            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $sheet->fromArray(['Sr.No.', 'Name', 'Email', 'Created At'], null, 'A1');

            // Data rows
            $rowIndex = 2;
            foreach ($users as $user) {
                $sheet->fromArray([
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->created_at->format('Y-m-d'),
                ], null, 'A' . $rowIndex++);
            }
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(30);
            $sheet->getColumnDimension('D')->setWidth(25);


            // Wrap text for better readability
            $sheet->getStyle('A1:D' . ($rowIndex - 1))->getAlignment()->setWrapText(true);

            // Bold headers
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);

            // Border around cells
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:D' . ($rowIndex - 1))->applyFromArray($styleArray);

            // Auto row height
            $sheet->getDefaultRowDimension()->setRowHeight(-1);
            // Write file to temporary location
            $writer = new Xlsx($spreadsheet);
            $fileName = 'User_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = storage_path('app/' . $fileName);
            $writer->save($tempFile);

            // Return file as download response
            return Response::download($tempFile)->deleteFileAfterSend(true);
        } elseif ($key == 'School') {
            // dd($key);
            $schools = School::select('id', 'name', 'school_code', 'email', 'created_at')->latest()->get();
            if ($schools->isEmpty()) {
                return redirect()->back()->with('error', 'No data found');
            }
            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $sheet->fromArray(['Sr.No.', 'Name', 'School Code', 'School Email', 'Created At'], null, 'A1');

            // Data rows
            $rowIndex = 2;
            foreach ($schools as $school) {
                $sheet->fromArray([
                    $school->id,
                    $school->name,
                    $school->school_code,
                    $school->email ?? 'N/A',
                    Carbon::parse($school->created_at)->format('Y-m-d'),
                ], null, 'A' . $rowIndex++);
            }
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(30);
            $sheet->getColumnDimension('E')->setWidth(25);

            // Wrap text for better readability
            $sheet->getStyle('A1:E' . ($rowIndex - 1))->getAlignment()->setWrapText(true);

            // Bold headers
            $sheet->getStyle('A1:E1')->getFont()->setBold(true);

            // Border around cells
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:E' . ($rowIndex - 1))->applyFromArray($styleArray);

            // Auto row height
            $sheet->getDefaultRowDimension()->setRowHeight(-1);

            // Write file to temporary location
            $writer = new Xlsx($spreadsheet);
            $fileName = 'School_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = storage_path('app/' . $fileName);
            $writer->save($tempFile);

            // Return file as download response
            return Response::download($tempFile)->deleteFileAfterSend(true);
        } elseif ($key == 'Video Content') {
            $request->validate([
                'category_id' => 'required|string',
                'is_featured' => 'required|in:yes,no',
                'user_type' => 'required|in:child,parent',
            ]);
            $category_id = $request->category_id;
            $is_featured = $request->is_featured;
            $user_type = $request->user_type;
            // dd($request->all());
            // Fetch data
            if ($request->category_id || $is_featured || $user_type) {
                $videoContent = \App\Models\VideoContent::with('category')
                    ->when($category_id, fn($q) => $q->where('category_id', $category_id))
                    ->when($is_featured, fn($q) => $q->where('is_featured', $is_featured))
                    ->when($user_type, fn($q) => $q->where('user_type', $user_type)) // ensure user_type column exists
                    ->get();
            } else {
                $videoContent = \App\Models\VideoContent::with('category')->get();
            }
            if ($videoContent->isEmpty()) {
                return redirect()->back()->with('error', 'No data found for the selected category.');
            }

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $sheet->fromArray([
                'Sr.No.',
                'Category Name',
                'Title',
                'Description',
                'Video Link',
                'User Type',
                'Is Featured',
                'Status',
                'Created At'
            ], null, 'A1');

            // Data rows
            $rowIndex = 2;

            foreach ($videoContent as $video) {
                $sheet->fromArray([
                    $video->id,
                    $video->category->category_name ?? 'N/A',
                    $video->title,
                    $video->description,
                    asset('assets/video/' . $video->video_link),
                    $video->user_type,
                    $video->is_featured,
                    $video->status,
                    Carbon::parse($video->created_at)->format('Y-m-d'),
                ], null, 'A' . $rowIndex++);
            }
            // Set column widths for better visibility
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Wrap text for better readability
            $sheet->getStyle('A1:I' . ($rowIndex - 1))->getAlignment()->setWrapText(true);

            // Bold headers
            $sheet->getStyle('A1:I1')->getFont()->setBold(true);

            // Border around cells
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:I' . ($rowIndex - 1))->applyFromArray($styleArray);

            // Auto row height
            $sheet->getDefaultRowDimension()->setRowHeight(-1);
            // Write file to temporary location
            $writer = new Xlsx($spreadsheet);
            $fileName = 'Video_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = storage_path('app/' . $fileName);
            $writer->save($tempFile);

            // Return file as download response
            return Response::download($tempFile)->deleteFileAfterSend(true);
        } elseif ($key == 'Knowledge Session') {
            $request->validate([
                'category_id' => 'required|string',
                'session_type' => 'required|in:online,offline,hybrid',
                'is_featured' => 'required|in:yes,no',
                'user_type' => 'required|in:child,parent',
            ]);
            // dd($request->all());
            $category_id = $request->category_id;
            $session_type = $request->session_type;
            $is_featured = $request->is_featured;
            $user_type = $request->user_type;

            // Fetch filtered data
            $sessions = \App\Models\KnowledgeSession::with('category')
                ->when($category_id, fn($q) => $q->where('category_id', $category_id))
                ->when($session_type, fn($q) => $q->where('session_type', $session_type))
                ->when($is_featured, fn($q) => $q->where('is_featured', $is_featured))
                ->when($user_type, fn($q) => $q->where('user_type', $user_type)) // ensure user_type column exists
                ->get();

            if ($sessions->isEmpty()) {
                return redirect()->back()->with('error', 'No data found for the selected filters.');
            }

            // Create Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header row
            $sheet->fromArray([
                'Sr.No',
                'Category Name',
                'Title',
                'Description',
                'Session Type',
                'User Type',
                'Is Featured',
                'Status',
                'Session Date',
                'Session Time',
                'Created At'
            ], null, 'A1');

            // Data rows
            $rowIndex = 2;
            foreach ($sessions as $session) {
                $sheet->fromArray([
                    $session->id,
                    $session->category->category_name ?? 'N/A',
                    $session->title,
                    $session->description,
                    ucfirst($session->session_type),
                    ucfirst($session->user_type ?? 'N/A'), // assuming nullable
                    ucfirst($session->is_featured),
                    ucfirst($session->status),
                    $session->session_date,
                    $session->session_time,
                    Carbon::parse($session->created_at)->format('Y-m-d'),
                ], null, 'A' . $rowIndex++);
            }

            // Set column widths for better visibility
            foreach (range('A', 'K') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Wrap text and style
            $sheet->getStyle('A1:K' . ($rowIndex - 1))->getAlignment()->setWrapText(true);
            $sheet->getStyle('A1:K1')->getFont()->setBold(true);
            $sheet->getStyle('A1:K' . ($rowIndex - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                ],
            ]);

            // Save and return the file
            $writer = new Xlsx($spreadsheet);
            $fileName = 'KnowledgeSession_' . now()->format('Ymd_His') . '.xlsx';
            $tempFile = storage_path('app/' . $fileName);
            $writer->save($tempFile);

            return Response::download($tempFile)->deleteFileAfterSend(true);
        }
        // elseif($key == 'Products')
        // {
        //     Session::put('tier', $request->tier);
        //     Session::put('location_id', $request->location_id);
        //     return Excel::download(new ExportProduct, 'Products' . date("YmdHis") . '.xlsx');   
        // }
        // elseif($key == 'Dept Codes')
        // {
        //     return Excel::download(new ExportDeptCodes, 'Dept Codes' . date("YmdHis") . '.xlsx');   
        // }
        // elseif($key == 'Clients')
        // {
        //     $from_dob_date = '';
        //     $to_dob_date = '';
        //     if(!empty($request->from_dob_date) && !empty($request->to_dob_date))
        //     {
        //         $currntYear = date('Y');
        //         $from_dob_date = $request->from_dob_date.' '.$currntYear;
        //         $to_dob_date = $request->to_dob_date.' '.$currntYear;
        //         $from_dob_date = date("d-m-Y",strtotime($from_dob_date));
        //         $to_dob_date = date("d-m-Y",strtotime($to_dob_date));
        //     }
        //     Session::put('order_status', $request->order_status);
        //     Session::put('from_dob_date', $from_dob_date);
        //     Session::put('to_dob_date', $to_dob_date);
        //     return Excel::download(new ExportClients, 'Clients' . date("YmdHis") . '.xlsx');   
        // }
        // elseif($key == 'Test')
        // {
        //     $from_dob_date = '';
        //     $to_dob_date = '';
        //     if(!empty($request->from_dob_date) && !empty($request->to_dob_date))
        //     {
        //         $currntYear = date('Y');
        //         $from_dob_date = $request->from_dob_date.' '.$currntYear;
        //         $to_dob_date = $request->to_dob_date.' '.$currntYear;
        //         $from_dob_date = date("d-m-Y",strtotime($from_dob_date));
        //         $to_dob_date = date("d-m-Y",strtotime($to_dob_date));
        //     }
        //     Session::put('order_status', $request->order_status);
        //     Session::put('from_dob_date', $from_dob_date);
        //     Session::put('to_dob_date', $to_dob_date);
        //     return Excel::download(new Test, 'Test' . date("YmdHis") . '.xlsx');   
        // }
        // elseif($key == 'Orders')
        // {
        //     Session::put('order_status', $request->order_status);
        //     Session::put('from_order_date', $request->from_order_date);
        //     Session::put('to_order_date', $request->to_order_date);
        //     Session::put('from_delivery_date', $request->from_delivery_date);
        //     Session::put('to_delivery_date', $request->to_delivery_date);
        //     Session::put('from_dispatch_date', $request->from_dispatch_date);
        //     Session::put('to_dispatch_date', $request->to_dispatch_date);
        //     return Excel::download(new ExportOrders, 'Orders' . date("YmdHis") . '.xlsx');   
        // }
    }
}
