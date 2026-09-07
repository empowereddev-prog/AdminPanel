<?php

namespace App\Services;

use App\Models\User;
use App\Models\TempUser;
use App\Models\RideBooking;
use App\Models\School;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class RegisterService
{
    public function register(array $data)
    {
        $language = $data['language'] ?? 'english';
        $otp = ___otp_code();
        $data['otp'] = $otp;
        $data['otp_expires_at'] = Carbon::now()->addMinutes(5);
        $data['password'] = Hash::make($data['password']);
        $schoolCode = $data['school_code'] ?? null;

        $school = null;

        if ($schoolCode) {
            $school = School::where('school_code', $schoolCode)
                ->where('status', 'active')
                ->first();

            if (!$school) {
                return [
                    'status' => false,
                    'message' => $language == 'english'
                        ? 'The school code you entered is not valid. Please check and try again.'
                        : '您输入的学校代码无效，请检查后再试。',
                    'user' => null,
                ];
            }
        }

        $user_data = [
            'name' => $data['name'],
            'email' => $data['email'],
            'country_code' => $data['country_code'],
            'phone_no' => $data['phone_no'],
            'password' => $data['password'],
            'otp' => $data['otp'],
            'otp_expires_at' => $data['otp_expires_at'],
            'terms_n_conditions_accepted' => $data['terms_n_conditions_accepted'],
            'user_type' => $data['user_type'],
            'status' => 'active',
            'user_role_id' => 3,
            'school_id' => $school?->id
        ];

        // Check for existing user
        $existingUser = User::where(function ($query) use ($data) {
            $query->where('email', $data['email'])
                ->orWhere('phone_no', $data['phone_no']);
        })->where('user_role_id', 3)->whereNull('deleted_at')->first();

        if ($existingUser) {
            if ($existingUser->is_mobile_verified == 'yes') {
                return [
                    'status' => false,
                    'message' => $language == 'english' ? 'The phone number or email is already registered.' : '该电话号码或电子邮件已经注册。',
                    'user' => null,
                ];
            } else {
                // Update existing unverified user
                $existingUser->update($user_data);
                $user = $existingUser;
            }
        } else {

            // ✅ Create subscription if registered via school code
            $user = User::create($user_data);

            if ($school) {

                $subscriptionType = $school->subscription_type; // monthly / quarterly / yearly
                $startDate = Carbon::now();

                $endDate = match ($subscriptionType) {
                    'monthly'   => $startDate->copy()->addMonth(),
                    'quarterly' => $startDate->copy()->addMonths(3),
                    'yearly'    => $startDate->copy()->addYear(),
                    default     => $startDate->copy()->addMonth(),
                };

                // 🔒 Prevent duplicate subscription
                $existingSubscription = Subscription::where('user_id', $user->id)
                    ->where('user_type', 'parent')
                    ->exists();

                if (!$existingSubscription) {
                    Subscription::create([
                        'user_id' => $user->id,
                        'subscription_type_id' =>
                        $subscriptionType === 'monthly'
                            ? 'com.empowered.monthly'
                            : ($subscriptionType === 'quarterly'
                                ? 'com.empowered.quarterly'
                                : 'com.empowered.yearly'),
                        'user_type' => 'parent',
                        'subscription_type' => $subscriptionType,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'currency' => 'SGD',
                        'status' => 'Successful',
                        'price' =>
                        $subscriptionType === 'monthly'
                            ? '13.49'
                            : ($subscriptionType === 'quarterly'
                                ? '33.81'
                                : '101.63'),
                    ]);
                }
            }
        }

        $encryptedEmail = Crypt::encryptString($user->email);

        // Send email verification link
        // $language = 'english';
        $emailData = [
            'name' => $user->name,
            'email' => $user->email,
            'link' => route('user.verify', ['user_id' => $user->id, 'email' => $encryptedEmail])
        ];
        ___mail_sender($emailData['email'], 'verification_email', $emailData, $language);
        // Send OTP to user
        // Your EmpowerED OTP is: " . $otp . "Do not share this code with anyone.
        $message = "Your EmpowerED OTP is: " . $otp . " Do not share this code with anyone.";
        ___sms_sender($message, $data['country_code'] . $data['phone_no'], $language);


        return [
            'status' => true,
            'message' => $language == 'english' ? 'OTP sent successfully' : 'OTP 发送成功',
            'user' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'country_code' => $user->country_code,
                'phone_no' => $user->phone_no
            ]
        ];
    }


    public function verifyOtp(array $data)
    {
        $user = User::where('id', $data['user_id'])->first();
        // Check if the phone number is already verified
        $language = $user->language;
        if (!is_null($user->phone_no_verified_at)) {
            if ($user->phone_no_verified_at > now()) {
                return [
                    'status' => false,
                    'message' => $language == 'english' ? 'Phone number is already verified.' : '电话号码已经验证。'
                ];
            }
        }
        // Check if the OTP has expired before validating
        if (___carbon_now()->greaterThan($user->otp_expires_at)) {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'OTP has expired. Please request a new one.' : 'OTP 已过期。请申请新的'
            ];
        }
        // Temporary OTP for verification purposes
        // if ($data['otp'] == "111111") {
        //     // Update phone verification time
        //     $user->update(['phone_no_verified_at' => ___carbon_now(),'phone_no' => $data['phone_no']]);

        //     // OTP is valid
        //     return [
        //         'status' => true,
        //         'message' => 'OTP verified successfully.'
        //     ];
        // }

        // Validate OTP
        if ($user->otp !== $data['otp']) {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'Invalid OTP.' : '无效的 OTP。'
            ];
        }

        // Update phone verification time
        $user->update(['phone_no_verified_at' => ___carbon_now(), 'phone_no' => $data['phone_no']]);

        // OTP is valid
        return [
            'status' => true,
            'message' => $language == 'english' ? 'OTP verified successfully.' : 'OTP 验证成功。'
        ];
    }

    // public function resendOtp(array $data)
    // {
    //     $user = User::where('id', $data['user_id'])->first();
    //     $language = $user->language;
    //     // Check if the phone number is already verified
    //     if (!is_null($user->phone_no_verified_at)) {
    //         return [
    //             'status' => false,
    //             'message' =>  $language == 'english' ?'Phone number is already verified.':'电话号码已经验证。'
    //         ];
    //     }

    //     // Check if otp_expires_at exists, and if the user has requested OTP recently
    //     if (!is_null($user->otp_expires_at)) {
    //         // Convert otp_expires_at to Carbon instance
    //         $otpExpiryAt = ___carbon_parse($user->otp_expires_at);
    //         if (___carbon_now()->lessThan($otpExpiryAt)) {
    //             return [
    //                 'status' => false,
    //                 'message' =>  $language == 'english' ? 'You can only request OTP once every five minutes. Please try again later.':'您每五分钟只能请求一次 OTP。请稍后重试。'
    //             ];
    //         }
    //     }


    //     $otp = ___otp_code(); 
    //     $otpExpiresAt = ___carbon_now()->addMinutes(5); 


    //     $user->update([
    //         'otp' => $otp,
    //         'otp_expires_at' => $otpExpiresAt
    //     ]);

    //     // Send OTP to the user's phone number
    //     // ___sms_sender($user->phone_no, $otp); // Assuming ___sms_sender() sends the OTP
    //     $message = "Hello User, Your mobile verification code is $otp";
    //     ___sms_sender($message, $user->country_code . $user->phone_no);
    //     return [
    //         'status' => true,
    //         'message' =>  $language == 'english' ? 'OTP sent successfully.':'OTP 发送成功。'
    //     ];
    // }


    public function resendOtp(array $data)
    {
        $user = User::where('id', $data['user_id'])->first();
        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not found.'
            ];
        }

        $language = $user->language;

        // 1️⃣ Check if the phone number is already verified
        if (!is_null($user->phone_no_verified_at)) {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'Phone number is already verified.' : '电话号码已经验证。'
            ];
        }

        // 2️⃣ Check if user is blocked due to too many attempts
        if (!is_null($user->otp_blocked_until) && ___carbon_now()->lessThan(___carbon_parse($user->otp_blocked_until))) {
            return [
                'status' => false,
                'message' => $language == 'english'
                    ? 'Too many OTP requests. Please try again after ' . ___carbon_parse($user->otp_blocked_until)->diffForHumans()
                    : 'OTP 请求过多。请稍后再试。'
            ];
        }

        // 3️⃣ Reset otp_attempts and otp_blocked_until if the block period has expired
        if (!is_null($user->otp_blocked_until) && ___carbon_now()->greaterThanOrEqualTo(___carbon_parse($user->otp_blocked_until))) {
            $user->update([
                'otp_attempts' => 0,
                'otp_blocked_until' => null
            ]);
        }

        // 4️⃣ Restrict multiple OTP requests within 5 minutes
        if (!is_null($user->otp_expires_at)) {
            $otpExpiryAt = ___carbon_parse($user->otp_expires_at);
            if (___carbon_now()->lessThan($otpExpiryAt)) {
                return [
                    'status' => false,
                    'message' => $language == 'english'
                        ? 'You can only request OTP once every five minutes. Please try again later.'
                        : '您每五分钟只能请求一次 OTP。请稍后重试。'
                ];
            }
        }

        // 5️⃣ Check OTP attempt count
        if ($user->otp_attempts >= 3) {
            $blockedUntil = ___carbon_now()->addMinutes(30); // Block user for 30 minutes
            $user->update([
                'otp_blocked_until' => $blockedUntil,
                'otp_attempts' => 0 // Reset attempts after blocking
            ]);

            return [
                'status' => false,
                'message' => $language == 'english'
                    ? 'You have exceeded the maximum OTP attempts. Please try again after 30 minutes.'
                    : '您已超过最大 OTP 尝试次数。请在 30 分钟后重试。'
            ];
        }

        // 6️⃣ Generate new OTP and expiry time
        $otp = ___otp_code();
        $otpExpiresAt = ___carbon_now()->addMinutes(5);

        // 7️⃣ Increment OTP attempt count
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
            'otp_attempts' => $user->otp_attempts + 1
        ]);

        // 8️⃣ Send OTP via SMS
        $message = "Your EmpowerED OTP is: " . $otp . "Do not share this code with anyone.";
        ___sms_sender($message, $user->country_code . $user->phone_no, $language);

        return [
            'status' => true,
            'message' => $language == 'english' ? 'OTP sent successfully.' : 'OTP 发送成功。'
        ];
    }
}
