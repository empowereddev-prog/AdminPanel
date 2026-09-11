<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;


class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout']]);
    }

    public function loginPage()
    {
        return view('admin.login');
    }

    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
            // 'password' => 'required|string|min:4|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'password' => 'required|string|min:4',
        ], [
            'email.required' => 'The email field is required.',
            'email.regex' => 'Please enter valid email.',
            'password.required' => 'The password field is required.',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::where(['email' => $request->email])->whereIn('user_role_id', ['1', '2'])->first();
        if (!$user) {
            return back()->with(['fail' => 'Your account does not exist.']);
        } elseif (!\Hash::check($request->password, $user->password)) {
            return back()->with('fail', 'Invalid Credentials.');
        } elseif ($user->status == 'inactive') {
            return back()->with('fail', 'Your account is inactive. Please contact to admin.');
        }

        if (app()->environment('local')) {
            Auth::guard('admin')->login($user);
            return redirect()->route('admin.dashboard')->with('success', 'Login successfully done.');
        }

        $this->sendAdminLoginOtp($user);
        $encryptedEmail = Crypt::encryptString($request->email);
        return redirect()->route('otp_verification',['email' => $encryptedEmail])->with('success', 'OTP Send Successfully');
    }

    // public function adminLogin(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|regex:/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
    //         'password' => 'required|string|min:4',
    //     ], [
    //         'email.required' => 'The email field is required.',
    //         'email.regex' => 'Please enter valid email.',
    //         'password.required' => 'The password field is required.',
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     $user = User::where(['email' => $request->email])->whereIn('user_role_id', ['1', '2'])->first();

    //     if (!$user) {
    //         return back()->with(['fail' => 'Your account does not exist.']);
    //     } elseif (!\Hash::check($request->password, $user->password)) {
    //         return back()->with('fail', 'Invalid Credentials.');
    //     } elseif ($user->status == 'inactive') {
    //         return back()->with('fail', 'Your account is inactive. Please contact to admin.');
    //     }

    //     Auth::guard('admin')->login($user);

    //     return redirect()->route('admin.dashboard')->with('success', 'Login successfully done.');
    // }

    public function otpVerification($email){
        return view('admin.otp',compact('email'));
    }

    public function verifyOtp(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ], [
            'otp.required' => 'Please enter OTP.',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $email = Crypt::decryptString($request->email);
        // Fetch user based on email and role
        $user = User::where('email', $email)->whereIn('user_role_id', [1, 2])->first();
        // Check if user exists
        if (!$user) {
            return back()->with('fail', 'User not found.');
        }
        // Validate OTP
        // if ($user->otp === $request->otp || $request->otp === '4444') {
        if ((string) $user->otp === (string) $request->otp) {
            // Update verification status
            $user->update(['is_verified' => '1']);
            Auth::guard('admin')->login($user);
            return redirect()->route('admin.dashboard')->with('success', 'Login Successfully Done');
        }
        return back()->with('fail', 'OTP does not match.');
    }

    public function adminResendOtp(Request $request){
            $email = Crypt::decryptString($request->email);
            $user = User::where('email', $email)->whereIn('user_role_id', [1, 2])->first();
            if (!$user) {
                return back()->with('fail', 'User not found.');
            }
            $this->sendAdminLoginOtp($user);
            return back()->with('success', 'OTP resent successfully!');

    }

    private function sendAdminLoginOtp(User $user): void
    {
        $otp = ___otp_code();
        $user->update(['otp' => (string) $otp]);

        $data = [
            'name' => $user->name ?: 'Admin',
            'email' => $user->email,
            'otp' => (string) $otp,
            'year' => (string) date('Y'),
        ];

        ___mail_sender(config('mail.admin_otp_recipient'), 'admin_otp', $data, 'english');
    }
    public function forgotPage()
    {
        return view('admin.forgetPassword');
    }

    public function resetPasswordLink(Request $request)
    {
        $language = 'english';
        $request->validate([
            'email' => 'required|regex:/(.+)@(.+)\.(.+)/i|',
        ],[
            'email.required' => 'Email is required.'
        ]);

        $mail = $request->email;
        $user = User::where('email', $mail)->whereIn('user_role_id',[1,2])->first();
        $reset = md5(microtime());

        if ($user) {
            $user->password_reset_code = $reset;
            $expirationTime = now()->addMinutes(10);
            $user->password_reset_expires_at = $expirationTime;
            $user->save();
            $email['name'] = $user->name;
            $email['email'] = $user->email;
            // $email['expTime'] =  $expirationTime ;
            $email['link'] = '<a href="' . route('reset.password.page', ['token' => $reset]) . '">Click Here</a>';
            ___mail_sender($mail, 'forgot_password', $email,$language);

            return back()->with('pass', 'Mail Sent Successfully.');
        } else {
            return back()->with('fail', "Email doesn't Exists.");
        }

    }

    public function resetPasswordPage(Request $request, $token)
    {
        $user = User::where('password_reset_code', $token)->first();

        if ($user && now() <= $user->password_reset_expires_at) {
            $decryptedPassword = $user->password;
            // dd($decryptedPassword);
            return view('admin.resetPassword', compact('token','decryptedPassword'));
        } else {
            return view('admin.resetExpire', compact('token'));
        }

    }

    public function passwordReset(Request $request, $token)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            // 'password' => 'required|string|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            // 'confirm_password' => 'required|string|min:8|same:password|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'old_password' => 'required',
            'password' => 'required|min:8|max:15|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{8,15}$/',
            'confirm_password' => 'required|same:password|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{8,15}$/',
        ], [
            // 'password.required' => 'The new password field is required',
            // 'confirm_password.required' => 'The confirm password field is required',
            // 'password.regex' => 'The new password must contain 1 Upper Case,1 Lower Case, 1 Numeric and 1 Special Character.',
            // 'confirm_password.regex' => 'The confirm password must contain 1 Upper Case,1 Lower Case, 1 Numeric and 1 Special Character.',
            // 'confirm_password.same' => 'The new password and confirm Password should be same.',|
            'old_password.required' => "Old password is required.",
            'password.required' => "New password is required.",
            'confirm_password.required' => "Confirm password is required.",
            'password.regex' => "The password must be between 8 and 15 characters long and must contain at least one uppercase letter, one lowercase letter, and one numeric digit.",
             'confirm_password.same' => 'The new password and confirm password must be the same.',
        ]);

        $validator->after(function ($validator) use ($request) {
            if (Hash::check($request->old_password, Hash::make($request->password))) {
                $validator->errors()->add('password', 'The new password must be different from the current password.');
            }
        });
        // dd($request->all(),Hash::make($request->password),Hash::make($request->old_password),(Hash::make($request->password)== Hash::make($request->old_password)));
        if ($validator->fails()) {
            // dd($validator);
            return redirect()->back()->withErrors($validator)->withInput();
        }
        // dd($confirm_password);
        $user = User::where('password_reset_code', $token)->first();
        if ($request->password == $request->confirm_password) {
            $user->password = Hash::make($request->password);
            // $user->password_reset_code = null;
            $user->save();
           // return redirect('/')->with('success', 'Password changed successfully.');
            // Redirect based on user role
            if ($user->user_role_id == 3) {
                return redirect()->back()->with('pass', 'Password changed successfully.');
            } elseif (in_array($user->user_role_id, [1, 2])) {
                return redirect()->back()->with('pass', 'Password changed successfully.');
            }

        } else {
            return back()->with('fail', "Passwords doesn't match.");
        }

    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/');
    }

    public function reset(Request $request, $token){
        $user = User::where('password_reset_code', $token)->first();
        $user->password_reset_code = null;
        $user->save();
        if ($user->user_role_id == 3) {
            return redirect()->away('https://empoweredhealth.asia/')->with('success', 'Password changed successfully.');
        } elseif (in_array($user->user_role_id, [1, 2])) {
            return redirect()->away('https://admin.empoweredhealth.asia/')->with('success', 'Password changed successfully.');
        }
    }
    public function showFormDelele()
    {
        return view('delete-account');
    }
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Invalid email or password');
        }

        $user->delete();

        return redirect('/')->with('success', 'Account deleted successfully');
    }

}
