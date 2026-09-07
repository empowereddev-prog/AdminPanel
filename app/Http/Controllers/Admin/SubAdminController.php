<?php

namespace App\Http\Controllers\Admin;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PermissionUser;
use App\Models\User;
use App\Models\Country;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Yajra\Datatables\datatables;
use Validator;
class SubAdminController extends Controller
{
    //User Management
    private $role_permission = 4;
    private $subadmin_menu_id = 4;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function rolePermission(Request $request)
    {   
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id) ;
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        $data['title']="Role Permission";
        $rolePermission = User::where('user_role_id',2)->orderBy('id','DESC')->get();
       
         if ($request->ajax()) {
            if(!empty($pre) && $pre->is_modify == 'yes'){
                return Datatables::of($rolePermission)
                    ->addIndexColumn()
                    ->addColumn('action', function($rolePermission){
                       $btn = '<a href="'.route('rolePermission.edit',$rolePermission->id).'" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a> ';
                      
                         $btn .= '<a id="myevent1" href="javascript:void(0);"  data-url="'.route('rolePermission.destroy',$rolePermission->id).'" class="delete" title="Delete" data-id="' . $rolePermission->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>';
                    // $btn .= '<a href="' . url("user/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>&nbsp;';
                    // $btn .= '<a href="' . url("delete-user/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';
                        return $btn;
                })
                ->editColumn('status', function ($rolePermission){
                    return "<span class='sts $rolePermission->status'>" .ucfirst($rolePermission->status). "</span>";
                })
                ->rawColumns(['action','status'])
                ->make(true);
        }
        else{
            return Datatables::of($rolePermission)
                ->addIndexColumn()
                ->make(true);
            }
        }
        // dd($data);
        return view('admin.rolePermission.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function addRolePermission()
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if(!empty($pre) && $pre->is_modify == 'yes'){
            $data['title'] = 'Add Teacher Details';
            $data['countrycode']= Country::get();
            $Permission_user= [];
            return view('admin.rolePermission.add',compact('Permission_user', $Permission_user))->with($data);
        }
        return redirect('home');        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeRolePermission(Request $request)
    {
        // dd($request->all());
         // Define validation rules
                // Validate request data
                $validator = Validator::make($request->all(),[
                    'name'        => 'required|string|min:3|max:50',
                    'email'       => [
                        'required',
                        'email',
                        'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
                        function ($attribute, $value, $fail) {
                            if (\App\Models\User::where('email', $value)->where('user_role_id', 2)->exists()) {
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
                            if (\App\Models\User::where('phone_no', $value)->where('user_role_id', 2)->exists()) {
                                $fail('The phone number has already been taken for this role.');
                            }
                        },
                    ],
                    // 'status'      => 'required|in:active,inactive',
                ]
            );
            // dd($validator);

                // If validation fails, return error response
                if ($validator->fails()) {
                    return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
                }
            $password = 'E'.\Str::studly(Str::random(6).'@');
            // $password = ___encrypt($token);
            $users  = User::create([
                'name' => strip_tags($request->input('name')),
                'email' =>$request->input('email'),
                'country_code' => $request->input('code'),
                'phone_no' => $request->input('phone_no'),
                'user_role_id' => 2,
                'password' => Hash::make($password),
                'status' =>'active'

            ]);
            $success = [
                'name'=>$request->input('name'),
                'email'=>$request->input('email'),
                'password'=>$password,
            ];
            ___mail_sender($request->input('email'), 'signup_subadmin', $success,'english');
            $user_role = $request->parent;

            $user_management_menu = $request->user_management;
            $user_management_view = $request->user_management_view;
            $user_management_modify = $request->user_management_modify ?? 'no';


            //School Managment
            $school_management_menu = $request->school_management;
            $school_management_view = $request->school_management_view;
            $school_management_modify = $request->school_management_modify ?? 'no';

            //Role permission
            $role_menu = $request->role_permission;
            $role_permission_view = $request->role_permission_view;
            $role_permission_modify = $request->role_permission_modify ?? 'no';

            //Avatar
            $avatar_menu = $request->avatar;
            $avatar_view = $request->avatar_view;
            $avatar_modify = $request->avatar_modify ?? 'no';
            //payment 
            $payment = $request->payment;
            $payment_view = $request->payment_view;
            $payment_modify = $request->payment_modify ?? 'no';

            //Contact Management
            $contact = $request->contact;
            $contactview = $request->contact_view;

            //Video content
            $management = $request->management;
            $management_view = $request->management_view;
            $management_modify = $request->management_modify1 ?? 'no';

            //Content Management
            $content = $request->content;
            $content_view = $request->content_view;
            $content_modify = $request->content_modify1 ?? 'no';

            //setting
            $setting_menu = $request->setting;
            $setting_view = $request->setting_view;
            $setting_modify = $request->setting_modify ?? 'no';

            // quiz management
            $quiz_menu = $request->quiz_management;
            $quiz_view = $request->quiz_management_view;
            $quiz_modify = $request->quiz_management_modify1 ?? 'no'; 

            $deletequery = PermissionUser::deletepermission($users->id);

            if (!empty($user_management_menu)) {
                $menu_id = '2';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($user_management_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $user_management_modify[$count] == 'yes' ? $user_management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => !empty($user_management_view) ? $user_management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($setting_menu)) {
                $menu_id = '12';
                $menudata1[] = $menu_id;
                $countt = 0;
                foreach ($setting_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $setting_modify[$countt] == 'yes' ? $setting_modify[$countt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => !empty($setting_view) ? $setting_view[0] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $countt++;
                }
            
            }
            if (!empty($content)) {
                $menu_id = '5';
                $menudata2 = [];
                $counttt = 0;
                
                foreach ($content as $value) {
                    $menu_id = $value;
                    $menudata2[] = $menu_id;
                    
                    $data['role'] = array(
                        'is_modify' => $content_modify[$counttt] == 'yes' ? $content_modify[$counttt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => isset($content_view[$counttt]) ? $content_view[$counttt] : 'no',
                    );
                    
                    PermissionUser::insertpermission($data['role']);
                    $counttt++;
                }


            }

            if (!empty($school_management_menu)) {
                $menu_id = '3';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($school_management_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $school_management_modify[$count] == 'yes' ? $school_management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' =>isset($school_management_view[$count]) ? $school_management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($avatar_menu)) {
                $menu_id = '13';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($avatar_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $avatar_modify[$count] == 'yes' ? $avatar_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => !empty($avatar_view) ? $avatar_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($payment)) {
                // dd($avatar_view);
                $menu_id = '23';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($payment as $value) {
                    $data['role'] = array(
                        'is_modify' => $payment_modify[$count] == 'yes' ? $payment_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => isset($payment_view[$count]) ? $payment_view[$count] : 'no',
                    );
                    // dd($data);
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($management)) {
                $menu_id = '15';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($management as $value) {
                    $data['role'] = array(
                        'is_modify' => $management_modify[$count] == 'yes' ? $management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => !empty($management_view) ? $management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($quiz_menu)) {
                $menu_id = '19';
                $menudata2 = [];
                $counttt = 0;
                
                foreach ($quiz_menu as $value) {
                    $menu_id = $value;
                    $menudata2[] = $menu_id;
                    
                    $data['role'] = array(
                        'is_modify' => $quiz_modify[$counttt] == 'yes' ? $quiz_modify[$counttt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => isset($quiz_view[$counttt]) ? $quiz_view[$counttt] : 'no',
                    );
                    
                    PermissionUser::insertpermission($data['role']);
                    $counttt++;
                }


            }
            // dd((!empty($request->role_permission) && !empty($role_permission_view)),$role_permission_view,$role_permission_modify);
            if (!empty($request->role_permission)) { // Ensure request exists
                $data['role'] = array(
                    'is_modify' => !empty($role_permission_modify) && $role_permission_modify[0] == 'yes' ? 'yes' : 'no',
                    'menu_id' => '4',
                    'user_id' => $users->id,
                    'is_view' => !empty($role_permission_view) && $role_permission_view[0] == 'yes' ? 'yes' : 'no',
                );

                $query = PermissionUser::insertpermission($data['role']);
            }

            if (!empty($contact)) {
                $menu_id = '11';
                $menudata5[] = $menu_id;
                $countttttt = 0;
                foreach ($contact as $value) {
                    $data['role'] = array(
                        // 'is_modify' => isset($contactmodify[$countttttt]) ? $contactmodify[$countttttt] : 'no',
                        'is_modify' => 'yes',
                        'menu_id' => $value,
                        'user_id' => $users->id,
                        'is_view' => $contactview[$countttttt] ?? 'yes',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $countttttt++;
                }
              
            }

            return redirect('role-permission')->with('added', 'Roles Added Successfully!'); 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRolePermission($id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if(!empty($pre) && $pre->is_modify == 'yes')
        {

            $data['title'] = 'Edit Teacher Details';
            $data['countrycode']= Country::get();
            $data['user'] = User::where('id',$id)->first();
            $data['Permission_user']  = _arrayfy(PermissionUser::where('user_id', $id)->get());
            // dd($data);
            return view('admin.rolePermission.edit')->with($data);  
        }
      return redirect('home');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateRolePermission(Request $request, $id)
    {
        $user = User::findOrFail($id); // Fetch the user
        $validator = Validator::make($request->all(),[
            'name'        => 'required|string|min:3|max:50',
            'code' => 'required|string|max:10',
            'phone_no'     => [
                'required',
                'numeric',
                'digits_between:8,15',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::where('phone_no', $value)
                            ->where('user_role_id', 2)
                            ->where('id', '!=', $user->id) // Exclude the current user
                            ->exists()) {
                        $fail('The phone number has already been taken for this role.');
                    }
                },
            ],
            // 'status'      => 'required|in:active,inactive',
        ]
);

        if ($validator->fails()) {
            return redirect()->back()
            ->withErrors($validator)
            ->withInput();
        }
        // dd($request->all());
        $updateArr = [
                'name' => strip_tags($request['name']),
                'email' => $request['email'],
                'country_code' =>$request['code'],
                'phone_no' => $request['phone_no'],
                'status' => $request['status']
            ];

            // $Users = User::findOrFail($id);
            $user->update($updateArr);
            $user_role = $request->parent;
           //User Management
           $user_management_menu = $request->user_management;
           $user_management_view = $request->user_management_view;
           $user_management_modify = $request->user_management_modify ?? 'no';


            //School Managment
            $school_management_menu = $request->school_management;
            $school_management_view = $request->school_management_view;
            $school_management_modify = $request->school_management_modify ?? 'no';

           //Role permission
           $role_menu = $request->role_permission;
           $role_permission_view = $request->role_permission_view;
           $role_permission_modify = $request->role_permission_modify ?? 'no';

           //Avatar
           $avatar = $request->avatar;
           $avatar_view = $request->avatar_view;
           $avatar_modify = $request->avatar_modify ?? 'no';

           $payment = $request->payment;
           $payment_view = $request->payment_view;
           $payment_modify = $request->payment_modify ?? 'no';

          //Contact Management
           $contact = $request->contact;
           $contactview = $request->contact_view;
           //Content Management
           $content = $request->content;
           $content_view = $request->content_view;
           $content_modify = $request->content_modify1 ?? 'no';
           //setting
           $setting_menu = $request->setting;
           $setting_view = $request->setting_view;
           $setting_modify = $request->setting_modify ?? 'no';
            //Video content
           $management = $request->management;
           $management_view = $request->management_view;
           $management_modify = $request->management_modify1 ?? 'no';

            // quiz management
            $quiz_menu = $request->quiz_management;
            $quiz_view = $request->quiz_management_view;
            $quiz_modify = $request->quiz_management_modify1 ?? 'no'; 


            $deletequery = PermissionUser::deletepermission($id);
            if (!empty($user_management_menu)) {
                $menu_id = '2';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($user_management_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $user_management_modify[$count] == 'yes' ? $user_management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => !empty($user_management_view) ? $user_management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
              
            }

            if (!empty($setting_menu)) {
                $menu_id = '12';
                $menudata1[] = $menu_id;
                $countt = 0;
                foreach ($setting_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $setting_modify[$countt] == 'yes' ? $setting_modify[$countt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => !empty($setting_view) ? $setting_view[0] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $countt++;
                }
                
            }
// dd(!empty($content_modify) ? $content_modify[1] : 'no');
            if (!empty($content)) {
                $menu_id = '5';
                $menudata2 = [];
                $counttt = 0;
                
                foreach ($content as $value) {
                    $menu_id = $value;
                    $menudata2[] = $menu_id;
                    
                    $data['role'] = array(
                        'is_modify' => $content_modify[$counttt] == 'yes' ? $content_modify[$counttt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => isset($content_view[$counttt]) ? $content_view[$counttt] : 'no',
                    );
                    
                    PermissionUser::insertpermission($data['role']);
                    $counttt++;
                }
            }

            if (!empty($school_management_menu)) {
                $menu_id = '3';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($school_management_menu as $value) {
                    $data['role'] = array(
                        'is_modify' => $school_management_modify[$count] == 'yes' ? $school_management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => isset($school_management_view[$count]) ? $school_management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
                
            }
            // dd($request->avatar);
            if (!empty($avatar)) {
                // dd($avatar_view);
                $menu_id = '13';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($avatar as $value) {
                    $data['role'] = array(
                        'is_modify' => isset($avatar_modify)  ? $avatar_modify[0] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => isset($avatar_view[$count]) ? $avatar_view[$count] : 'no',
                    );
                    // dd($data);
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($payment)) {
                // dd($avatar_view);
                $menu_id = '23';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($payment as $value) {
                    $data['role'] = array(
                        'is_modify' => $payment_modify[$count] == 'yes' ? $payment_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => isset($payment_view[$count]) ? $payment_view[$count] : 'no',
                    );
                    // dd($data);
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            
            if (!empty($management)) {
                $menu_id = '15';
                $menudata[] = $menu_id;
                $count = 0;
                foreach ($management as $value) {
                    $data['role'] = array(
                        'is_modify' => $management_modify[$count] == 'yes' ? $management_modify[$count] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => !empty($management_view) ? $management_view[$count] : 'no',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $count++;
                }
               
            }

            if (!empty($quiz_menu)) {
                $menu_id = '19';
                $menudata2 = [];
                $counttt = 0;
                
                foreach ($quiz_menu as $value) {
                    $menu_id = $value;
                    $menudata2[] = $menu_id;
                    
                    $data['role'] = array(
                        'is_modify' => $quiz_modify[$counttt] == 'yes' ? $quiz_modify[$counttt] : 'no',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => isset($quiz_view[$counttt]) ? $quiz_view[$counttt] : 'no',
                    );
                    
                    PermissionUser::insertpermission($data['role']);
                    $counttt++;
                }
            }
            if (!empty($request->role_permission)) { // Ensure request exists
                $data['role'] = array(
                    'is_modify' => !empty($role_permission_modify) && $role_permission_modify[0] == 'yes' ? 'yes' : 'no',
                    'menu_id' => '4',
                    'user_id' => $id,
                    'is_view' => !empty($role_permission_view) && $role_permission_view[0] == 'yes' ? 'yes' : 'no',
                );

                $query = PermissionUser::insertpermission($data['role']);
            }
            if (!empty($contact)) {
                $menu_id = '11';
                $menudata5[] = $menu_id;
                $countttttt = 0;
                foreach ($contact as $value) {
                    $data['role'] = array(
                        // 'is_modify' => isset($contactmodify[$countttttt]) ? $contactmodify[$countttttt] : 'no',
                        'is_modify' => 'yes',
                        'menu_id' => $value,
                        'user_id' => $id,
                        'is_view' => $contactview[$countttttt] ?? 'yes',
                    );
                    $query = PermissionUser::insertpermission($data['role']);
                    $countttttt++;
                }
            }
            return redirect('role-permission')->with('updated', 'Roles Updated Successfully!'); 
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteRolePermission($id)
    {
        $data = User::where('id',$id)->delete();
        if($data){
            return response()->json([
            'data'      => $data,
            'status'    => 'success',
            ]);
        }
    }

    public function changeUserStatus(Request $request){
        $data = User::where('id',$request->id)->update(['status'=>$request->status]); 
        if($data){
            // \Session::put('message','User status changed successfully.');
            return response()->json([
            'data'      => $data,
            'status'    => 'success',
            ]);
        }
    }
}
