<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMenu;
use App\Models\Country;
use App\Models\PermissionUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class PermissionSubAdminController extends Controller
{
    private $subadmin_menu_id = 4;

    public function index(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);

        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }

        $data['title'] = "Role Permission";
        $rolePermission = User::where('user_role_id', 2)->orderBy('id', 'DESC')->get();

        if ($request->ajax()) {
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($rolePermission)
                    ->addIndexColumn()
                    ->addColumn('action', function ($rolePermission) {
                        $btn = '<a href="' . route('assign-permission.edit', $rolePermission->id) . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil"></i></a>';
                        $btn .= '<a id="myevent1" href="javascript:void(0);" data-url="' . route('assign-permission.destroy', $rolePermission->id) . '" class="delete" title="Delete" data-id="' . $rolePermission->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>';
                        return $btn;
                    })
                    ->editColumn('status', function ($rolePermission) {
                        return "<span class='sts $rolePermission->status'>" . ucfirst($rolePermission->status) . "</span>";
                    })
                    ->rawColumns(['action', 'status'])
                    ->make(true);
            } else {
                return DataTables::of($rolePermission)->addIndexColumn()->make(true);
            }
        }

        return view('admin.assignPermission.index')->with($data);
    }

    public function create(Request $request)
    {

        $parentIds = AdminMenu::where('pid', '!=', 0)->pluck('pid')->unique();
        $excludeIds = [4, 6, 7, 8, 12, 13, 23, 28, 33, 34, 36, 37];
        $adminMenu = AdminMenu::whereNotIn('id', $parentIds)
            ->whereNotIn('id', $excludeIds)
            ->whereNotIn('id', [1])
            ->get();
        $countrycode = Country::get();

        return view('admin.assignPermission.add', compact('adminMenu', 'countrycode'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|min:3|max:50|not_regex:/<[^>]*>/u',
            'email' => [
                'required',
                'email',
                'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
                function ($attribute, $value, $fail) {
                    if (User::where('email', $value)->where('user_role_id', 2)->exists()) {
                        $fail('The email has already been taken for this role.');
                    }
                },
            ],
            'code' => 'required|string|max:10',
            'phone_no' => [
                'required',
                'numeric',
                'digits_between:8,15',
                function ($attribute, $value, $fail) {
                    if (User::where('phone_no', $value)->where('user_role_id', 2)->exists()) {
                        $fail('The phone number has already been taken for this role.');
                    }
                },
            ],
            'permissions' => 'required|array|min:1',
        ], [
            'permissions.required' => 'At least one permission must be selected.',
            'permissions.min' => 'At least one permission must be selected.',
        ]);


        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $password = 'E' . Str::studly(Str::random(6) . '@');

        $user = User::create([
            'name'     => strip_tags($request->input('name')),
            'email'    => $request->input('email'),
            'country_code' => $request->input('code'),
            'phone_no' => $request->input('phone_no'),
            'user_role_id' => 2,
            'password' => Hash::make($password),
            'status'   => 'active'
        ]);

        $success = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $password,
        ];
        ___mail_sender($request->input('email'), 'signup_subadmin', $success, 'english');

        PermissionUser::deletepermission($user->id);

        if ($request->has('permissions')) {
            foreach ($request->permissions as $menuId => $perm) {
                PermissionUser::insertpermission([
                    'user_id' => $user->id,
                    'menu_id' => $menuId,
                    'is_view' => $perm['is_view'] ?? 'no',
                    'is_modify' => $perm['is_modify'] ?? 'no',
                ]);
            }
        }

        return redirect()->route('assign-permission.index')->with('added', 'Roles Added Successfully!');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        // $allMenus = AdminMenu::where('id', '!=', 1)->get();
        $parentIds = AdminMenu::where('pid', '!=', 0)->pluck('pid')->unique();
        $excludeIds = [4, 6, 7, 8, 12, 13, 23, 33, 34, 28, 36, 37];
        $adminMenu = AdminMenu::whereNotIn('id', $parentIds)
            ->whereNotIn('id', $excludeIds)->whereNotIn('id', [1])->get();
        $countrycode = Country::all();

        $userPermissions = PermissionUser::where('user_id', $id)
            ->pluck('is_view', 'menu_id')->toArray();

        $userModifyPermissions = PermissionUser::where('user_id', $id)
            ->pluck('is_modify', 'menu_id')->toArray();

        return view('admin.assignPermission.edit', compact(
            'user',
            'adminMenu',
            'countrycode',
            'userPermissions',
            'userModifyPermissions'
        ));
    }
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|min:3|max:50|not_regex:/<[^>]*>/u',
            'email' => [
                'required',
                'email',
                'regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
                function ($attribute, $value, $fail) use ($id) {
                    if (User::where('email', $value)->where('user_role_id', 2)->where('id', '!=', $id)->exists()) {
                        $fail('The email has already been taken for this role.');
                    }
                },
            ],
            'code' => 'required|string|max:10',
            'phone_no' => [
                'required',
                'numeric',
                'digits_between:8,15',
                function ($attribute, $value, $fail) use ($id) {
                    if (User::where('phone_no', $value)->where('user_role_id', 2)->where('id', '!=', $id)->exists()) {
                        $fail('The phone number has already been taken for this role.');
                    }
                },
            ],
            'permissions' => 'required|array|min:1',
        ], [
            'permissions.required' => 'At least one permission must be selected.',
            'permissions.min' => 'At least one permission must be selected.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            'name' => strip_tags($request->input('name')),
            'email' => $request->input('email'),
            'country_code' => $request->input('code'),
            'phone_no' => $request->input('phone_no'),
            'status' => $request->status,
        ]);

        // Delete existing permissions and re-insert
        PermissionUser::deletepermission($user->id);

        if ($request->has('permissions')) {
            foreach ($request->permissions as $menuId => $perm) {
                PermissionUser::insertpermission([
                    'user_id' => $user->id,
                    'menu_id' => $menuId,
                    'is_view' => $perm['is_view'] ?? 'no',
                    'is_modify' => $perm['is_modify'] ?? 'no',
                ]);
            }
        }
        return redirect()->route('assign-permission.index')->with('success', 'Role updated successfully!');
    }

    public function delete($id)
    {
        $user = User::where('id', $id)->first();
        $emailData = [
            'name' => $user->name,
            'email' => $user->email,
        ];
        ___mail_sender($user->email, 'delete_sub_admin_account', $emailData, 'english');
        $user->delete();
        if ($user) {
            return response()->json([
                'data'      => $user,
                'status'    => 'success',
            ]);
        }
    }
}
