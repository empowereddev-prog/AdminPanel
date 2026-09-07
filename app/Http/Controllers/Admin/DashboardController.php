<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Pagination\LengthAwarePaginator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\ProfileRequest;
use App\Models\User;
use App\Models\PurchasedPlan;
use App\Models\Plan;
use App\Models\ProductModel;
use App\Models\ProductDataModel;
use File;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use DataTables;
use Carbon\Carbon;
use App\Models\School;
use App\Models\Child;
use App\Models\Country;
use App\Models\PaymentHistory;
use App\Models\Subscription;

class DashboardController extends Controller
{

    public function index()
    {
        $totalUser = User::where('user_role_id', '3')->where('status', 'active')->where('deleted_at', null)->whereNotNull('email_verified_at')->where('is_mobile_verified', 'yes')->whereNull('school_id')->count();
        $totalSchool = School::where('status', 'active')->count();
        $totalChild = Child::where('status', 'active')->count();
        $paymentHistory = Subscription::where('status', 'successful')->count();
        $items = [];
        $page = request()->query('page', 1); // Get the current page from the query string, default to 1 if not provided
        $perPage = 10; // Number of items per page
      
        $currentPageItems = collect($items)->slice(($page - 1) * $perPage, $perPage)->all();

        $items['paginator'] = new LengthAwarePaginator($currentPageItems, count($items), $perPage, $page);
        return view('admin.dashboard', compact('items', 'totalUser', 'totalSchool', 'totalChild','paymentHistory'));
    }
    public function getData(Request $request)
    {
        if ($request->ajax()) {
            // Base query
            $query = PurchasedPlan::query()->whereNull('deleted_at')->where('status', 'successful')->latest();

            // Apply filters if present
            if ($request->has('device_type')) {
                $query->where('device_type', $request->get('device_type'));
            }
            if ($request->has('plan') && $request->get('plan') != 'Plan') {
                $query->where('plan', 'like', "%" . $request->input('plan') . "%");
            }
            if ($request->has('date_range')) {
                // dd($request->get('date_range'));
                $dateRange = explode(' - ', $request->get('date_range'));
                $startDate = Carbon::createFromFormat('d-m-Y', $dateRange[0])->format('Y-m-d');
                $endDate = Carbon::createFromFormat('d-m-Y', $dateRange[1])->format('Y-m-d');
                $query->where('purchased_on', '>=', $startDate)->where('purchased_on', '<=', $endDate);
            }
            if ($request->has('sort') && $request->get('sort') !== 'sortBy') {
                $sortDirection = $request->get('sort') === 'desc' ? 'desc' : 'asc';
                $query->orderBy('created_at', $sortDirection);
            }
            $data = $query->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {

                    return '<a href="' . url("ordered-plan-details/" . $row->license_key) . '" title="Detail" style="margin-left:5px;font-size:20px"><i class="mdi mdi-eye"></i></a>';
                })
                ->editColumn('plan', function ($row) {
                    return "<span class='plan $row->plan'>" . ucfirst($row->plan) . "</span>";
                })
                ->editColumn('plan_type', function ($row) {
                    return ucfirst($row->plan_type);
                })
                ->editColumn('purchased_on', function ($row) {
                    return Carbon::parse($row->purchased_on)->format('d-m-Y');
                })
                ->editColumn('expires_on', function ($row) {
                    return Carbon::parse($row->expires_on)->format('d-m-Y');
                })
                ->rawColumns(['action', 'plan'])
                ->make(true);
        }
    }

    // public function getActivePlanDetails(Request $request, $licenseKey)
    // {
    //     $data = PurchasedPlan::where('license_key', $license)->get()->toArray();

    //     if ($data['plan_type'] == 'monthly') {
    //         $data['amount'] = Plan::where('plan', $data->plan)->value('monthly_amount');
    //     } else {
    //         $data['amount'] = Plan::where('plan', $data->plan)->value('annually_amount');
    //     }
    //     if($data['purchased_on']){
    //     $data['purchased_format'] = Carbon::parse($data->purchased_on)->format('d-m-Y');
    //     }
    //     if($data['expires_on']){
    //         $data['expired_format'] = Carbon::parse($data->expires_on)->format('d-m-Y');
    //         }



    //     // Get query parameters to use them in the view for navigating back
    //     return view('admin.planPurchased.detail', compact('data'));
    // }

    public function getActivePlanDetails(Request $request, $id)
    {
        $purchasedPlans = PurchasedPlan::where('license_key', $id)->get();
        $data = [];

        foreach ($purchasedPlans as $plansDetails) {
            // Determine the plan amount based on plan type (monthly or annually)
            if ($plansDetails->plan_type == 'monthly') {
                $amount = Plan::where('plan', $plansDetails->plan)->value('monthly_amount');
            } else {
                $amount = Plan::where('plan', $plansDetails->plan)->value('annually_amount');
            }

            // Format purchase date
            $purchasedFormat = $plansDetails->purchased_on
                ? Carbon::parse($plansDetails->purchased_on)->format('d-m-Y')
                : null;

            // Format expiration date
            $expiredFormat = $plansDetails->expires_on
                ? Carbon::parse($plansDetails->expires_on)->format('d-m-Y')
                : null;

            // Push data to the plansDetails array
            $data[] = [
                'order_id' => $plansDetails->order_id,
                'email' => $plansDetails->email,
                'plan' => $plansDetails->plan == 'basic' ? 'Basic' : 'Pro',
                'plan_type' => $plansDetails->plan_type == 'monthly' ? 'Monthly' : 'Annually',
                'amount' => $amount,
                'purchased_on' => $purchasedFormat,
                'expires_on' => $expiredFormat,
                'license_key' => $plansDetails->license_key,
            ];
            // dd($data);
        }

        // Get query parameters to use them in the view for navigating back
        return view('admin.planPurchased.detail', compact('data'));
    }
    public function loginPage()
    {
        return view('admin.login');
    }

    public function adminLogin(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ], [
            'email.required' => 'The email field is required.',
            'email.regex' => 'Please enter valid email.',
            'password.required' => 'The password field is required.',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::where(['email' => $request->email])->whereIn('user_type', ['admin', 'sub_admin'])->first();
        if (!$user) {
            return back()->with(['fail' => 'Your account does not exist.']);
        } elseif (!\Hash::check($request->password, $user->password)) {
            return back()->with('fail', 'Your Password does not match.');
        } elseif ($user->status == 'inactive') {
            return back()->with('fail', 'Your account is inactive. Please contact to admin.');
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password, 'user_type' => ['admin', 'sub_admin']]);
        return redirect()->route('admin.dashboard')->with('success', 'Login successfully.');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('admin')->with('success', 'Logout successfully.');
    }

    public function profile()
    {
        $user = Auth::user();
        $user['countries'] = Country::get();
        return view('admin.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user_id = Auth::id();
        $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9@.\'\-,]{3,}(?: [A-Za-z0-9@.\'\-,]+)*$/'],
            'country_code' => 'required|string|max:5|exists:countries,country_code',
            'phone_no' => [
                'required',
                'string',
                'max:15',
                function ($attribute, $value, $fail) use ($request) {
                    if (preg_match('/^(\d)\1+$/', $value)) {
                        $fail('The phone number cannot have all digits the same.');
                    }
                    if (substr($value, 0, 1) === '0') {
                        $fail('The phone number cannot start with 0.');
                    }
                    if ($request->country_code == '+91' && strlen($value) != 10) {
                        $fail('The phone number must be exactly 10 digits for Indian numbers.');
                    }
                    if ($request->country_code == '+65' && strlen($value) != 8) {
                        $fail('The phone number must be exactly 8 digits for Singapore numbers.');
                    }
                },
            ],
            'profile_image' => ['image', 'mimes:jpeg,png,jpg,gif'],
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $img = 'profile_image-' . time() . '-' . rand(0, 99) . '.' . $request->profile_image->extension();
            $request->profile_image->move(public_path('uploads/profile_img/'), $img);

            // Delete old image
            $oldpic = User::where('id', $user_id)->value('profile_image');
            if ($oldpic) {
                File::delete(public_path('uploads/profile_img/' . $oldpic));
            }

            User::where('id', $user_id)->update(['profile_image' => $img]);
        }

        // Update user details
        User::where('id', $user_id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'gender' => $request->gender,
            'country_code' => $request->country_code,
            'phone_no' => $request->phone_no,
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }


    public function changePassword()
    {
        return view('admin.change_password');
    }
    // public function updatePassword(ChangePasswordRequest $request)
    // {
    //     $admin = Auth::guard('admin')->user();

    //     if (!$admin) {
    //         return response()->json(['message' => 'Unauthorized'], 401);
    //     }

    //     if (!Hash::check($request->current_password, $admin->password)) {
    //         return response()->json(['errors' => ['current_password' => ['The old password is incorrect.']]], 422);
    //     }

    //     $admin->password = Hash::make($request->password);
    //     $admin->save();

    //     Auth::guard('admin')->logout(); // Logout user

    //     return response()->json(['message' => 'Password changed successfully. Redirecting...'], 200);
    // }

    public function updatePassword(ChangePasswordRequest $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('login')->with('error', 'Unauthorized');
        }

        if (!Hash::check($request->current_password, $admin->password)) {
            return redirect()->back()->withErrors(['current_password' => 'The old password is incorrect.']);
        }

        $admin->password = Hash::make($request->password);
        $admin->save();

        Auth::guard('admin')->logout();

        return redirect()->route('login')->with('success', 'Password changed successfully. Please log in again.');
    }
}
