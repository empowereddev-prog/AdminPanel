<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateTeacherProfileRequest;
use App\Models\AppNotification;
use App\Models\Child;
use App\Models\DeviceToken;
use App\Models\Role;
use App\Models\School;
use App\Models\Setting;
use App\Models\TempUser;
use App\Models\User;
use App\Rules\AtLeastOneNumberOrSpecialCharacter;
use App\Rules\PhoneValidation;
use App\Services\RegisterService;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\Token;

class HomeApiController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesApiUser;

    /**
     * Issue a Passport token for the authenticated user and record their device.
     *
     * The three login branches each carried their own copy of this block. They
     * had already drifted apart - that drift is what let any user log in through
     * the teacher endpoint - and each one also built a $tokenIds array that was
     * never used and wrote device_token/language two or three times over.
     */
    private function issueToken($user, ?string $deviceToken, ?string $language): string
    {
        $attributes = ['device_token' => $deviceToken];

        if ($language !== null) {
            $attributes['language'] = $language;
        }

        $user->update($attributes);

        if (!empty($deviceToken)) {
            DeviceToken::updateOrCreate(
                ['user_id' => $user->id],
                ['token' => $deviceToken]
            );
        }

        return $user->createToken('authToken')->accessToken;
    }


    protected $service;

    public function __construct(RegisterService $service)
    {
        $this->service = $service;
    }

    public function register(Request $request)
    {
        $language = $request->language;
        $validators = \Validator::make($request->all(), [
            'name' => 'required|min:3|max:50',
            'email' => 'required|regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
            'phone_no' => ['required', 'min:8', 'max:15', new PhoneValidation($request, $request->language)],
            'password' => [
                'min:8',
                'max:15',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
        ], [
            'name.required' => $language == 'english' ? 'Please enter your name' : '请输入您的姓名',
            'name.min' => $language == 'english' ? 'Your name must contain at least 3 characters.' : '您的姓名必须包含至少 3 个字符。.',
            'name.max' => $language == 'english' ? 'Name must not be greater than 30 characters.' : '名称不得超过 30 个字符。',
            'password.regex' => $language == 'english' ? 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character.' : '密码必须至少包含1个大写字母、1个小写字母、1个数字和1个特殊字符。',
            'email.required' => $language == 'english' ? 'The email field is required.' : '电子邮件字段是必需的。',
            'email.regex' => $language == 'english' ? 'Invalid email format' : '电子邮件格式无效',
            'password.confirmed' => $language == 'english' ? 'Confirm email and email does not match.' : '确认电子邮件与电子邮件不匹配。',
            'password.min' => $language == 'english' ? 'Your password must contain at least eight characters.' : '您的密码必须至少包含八个字符。',
            'password.max' => $language == 'english' ? 'Your password cannot contain more than 15 characters.' : '您的密码不能包含超过 15 个字符。',
            'phone.required' => $language == 'english' ? 'The phone field is required' : '电话字段为必填项',
        ]);

        if ($validators->passes()) {
            try {
                $response = $this->service->register(array_merge($request->only([
                    'name',
                    'email',
                    'country_code',
                    'phone_no',
                    'password',
                    'user_type',
                    'terms_n_conditions_accepted',
                    'status',
                    'school_code'
                ]), ['language' => $language]));
                return $response['status']
                    ? ApiResponse::success($response['user'], $response['message'], 200, ['user' => $response['user'] ?? []])
                    : ApiResponse::error($response['message'], 200, null, $response['user'], ['user' => $response['user'] ?? []]);
            } catch (\Throwable $th) {
                // This reported a failure as status: true and handed the raw
                // exception text - SQL included - straight to the client.
                \Log::error('register failed: ' . $th->getMessage());

                return ApiResponse::error(
                    $language == 'english'
                        ? 'Unable to register right now. Please try again later.'
                        : '目前无法注册。请稍后再试。',
                    200,
                    null,
                    null,
                    ['user' => []]
                );
            }
        }

        return ApiResponse::error($validators->errors()->first(), 200, null, null, ['user' => []]);
    }
    public function resendOtp(Request $request)
    {
        $request->validate([
            'country_code' => 'required',
            'phone_no' => 'required|exists:users,phone_no',
        ]);
        $user = User::where([
            ['phone_no', $request->phone_no],
            ['country_code', $request->country_code]
        ])->latest()->first();

        $language = $request->language;
        if (!$user) {
            return ApiResponse::error($language == 'english' ? 'User not found' : '未找到用户', 404);
        }
        $response = $this->service->resendOtp(['user_id' => $user->id]);

        return response()->json($response);
    }

    // public function login(Request $request)
    // {
    //     // try {
    //     if ($request->type == 'parent') {
    //         $typeId = Role::where('name', $request->type)->value('id');
    //         $user_id = User::where('country_code', $request->country_code)->where('phone_no', $request->phone_no)->where('user_role_id', $typeId)->whereNull('school_id')->first();
    //         $language = $request->language;

    //         if (empty($request->phone_no)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? "The phone_no field is required." : "phone_no 字段是必填项。",
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //         if (empty($user_id)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? "User doesn't exist." : "用户不存在。",
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //         $input = [
    //             'phone_no' => $request->phone_no,
    //             'password' => $request->password,
    //             'user_role_id' => $typeId,
    //         ];
    //         $validate_data = [
    //             'phone_no' => 'required',
    //             'password' => ['required'],
    //         ];
    //         $custom_messages = [
    //             'password.required' => $language == 'english' ? 'The password field is required.' : '密码字段是必填项。',
    //         ];
    //         $validator = Validator::make($input, $validate_data, $custom_messages, [], ['phone_no' => 'numeric']);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $validator->errors()->first(),
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //         $credentials = auth()->attempt($input);

    //         //  $typeId = Role::where('name',$request->type)->value('id');
    //         // dd(auth()->user());
    //         if (empty($credentials)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。',
    //                 'data' => (object) []
    //             ], 200);
    //         } elseif ($credentials && auth()->user()->status == 'inactive') {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐号已停用。请联系技术支持。',
    //                 'data' => (object) []
    //             ], 200);
    //         } elseif ($credentials) {
    //             if (auth()->user()->is_mobile_verified == 'no') {
    //                 $typeId = Role::where('name', $request->type)->value('id');
    //                 $data = User::where('phone_no', $request->phone_no)->where('user_role_id', $typeId)->first();
    //                 $otp = ___otp_code();
    //                 $message = "Hello User, Your mobile verification code is $otp";
    //                 ___sms_sender($message, $data->country_code . $data->phone_no, $language);
    //                 User::where('id', $data->id)->update(['mobile_otp' => $otp]);
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => $language == 'english' ? 'Please verify your mobile number ' : '请验证您的手机号码',
    //                     'data' => (object) []
    //                 ], 200);
    //             } elseif (empty(auth()->user()->email_verified_at)) {
    //                 $encryptedEmail = Crypt::encryptString(auth()->user()->email);


    //                 $language = 'english';
    //                 $emailData = [
    //                     'name' => auth()->user()->name,
    //                     'email' => auth()->user()->email,
    //                     'link' => route('user.verify', ['user_id' => auth()->user()->id, 'email' => $encryptedEmail])
    //                 ];
    //                 ___mail_sender($emailData['email'], 'verification_email', $emailData, $language);
    //                 return response()->json([
    //                     'status' => false,
    //                     'message' => $language == 'english' ? 'Please verify your email ' : '请验证您的电子邮件 ',
    //                     'data' => (object) []
    //                 ], 200);
    //             }
    //             auth()->user()->update(['device_token' => $request->device_token]);
    //             $token = auth()->user()->createToken('authToken')->accessToken;
    //             if (!empty($request->device_token)) {
    //                 $notification = DeviceToken::updateOrCreate(
    //                     ['user_id' => auth()->id()],
    //                     ['token' => $request->device_token]
    //                 );
    //             }
    //             $tokenIds = DeviceToken::select('id')->where('user_id', auth()->user()->id)->get()->toArray();
    //             $tokenArray = [];
    //             foreach ($tokenIds as $key => $value) {
    //                 array_push($tokenArray, $value['id']);
    //             }
    //             User::where('id', auth()->user()->id)->update(['device_token' => $request->device_token]);
    //             User::where('id', auth()->user()->id)->update(['language' => $language]);
    //             return response()->json([
    //                 'status' => true,
    //                 'message' => $language == 'english' ? "User logged in successfully." : "用户登录成功。",
    //                 'token' => $token,
    //                 'data' => auth()->user(),
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。',
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //     } elseif ($request->type == 'child') {
    //         $user_id = User::where('user_role_id', 4)->first();
    //         $language = $request->language;

    //         if (empty($user_id)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? "User doesn't exist." : "用户不存在。",
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //         $input = [
    //             'username' => $request->username,
    //             'password' => $request->password,
    //             'user_role_id' => 4,
    //         ];
    //         $validate_data = [
    //             'username' => 'required',
    //             'password' => ['required'],
    //         ];
    //         $custom_messages = [
    //             'username.required' => $language == 'english' ? 'The username field is required.' : '密码字段是必填项。',
    //         ];
    //         $validator = Validator::make($input, $validate_data, $custom_messages, [], ['username' => 'string']);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $validator->errors()->first(),
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //         $credentials = auth()->attempt($input);

    //         //  $typeId = Role::where('name',$request->type)->value('id');
    //         // dd(auth()->user());
    //         if (empty($credentials)) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。',
    //                 'data' => (object) []
    //             ], 200);
    //         } elseif ($credentials && auth()->user()->status == 'inactive') {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐号已停用。请联系技术支持。',
    //                 'data' => (object) []
    //             ], 200);
    //         } elseif ($credentials) {
    //             // Restrict the Login for Age Violation
    //             if (!empty(auth()->user()->dob)) {
    //                 try {
    //                     // Append day since dob is stored as Y-m format (e.g. 2002-04)
    //                     $dob = Carbon::createFromFormat('Y-m-d', auth()->user()->dob . '-01');

    //                     \Log::info('DOB check — name: ' . auth()->user()->name . ' | dob: ' . auth()->user()->dob . ' | age: ' . $dob->age);

    //                     if ($dob->age >= 18) {
    //                         auth()->logout();

    //                         return response()->json([
    //                             'status' => false,
    //                             'message' => $language == 'english'
    //                                 ? 'Account deactivated due to age limit.'
    //                                 : '账户因年龄限制已停用。',
    //                             'data' => (object) []
    //                         ], 200);
    //                     }

    //                 } catch (\Exception $e) {
    //                     \Log::error('DOB Parse Error: ' . $e->getMessage());
    //                 }
    //             }

    //             $imagePath = null;
    //             if ($request->hasFile('image')) {
    //                 $imagePath = $request->file('image')->store('children', 'public');
    //             }
    //             auth()->user()->update(['device_token' => $request->device_token]);
    //             $token = auth()->user()->createToken('authToken')->accessToken;
    //             if (!empty($request->device_token)) {
    //                 $notification = DeviceToken::updateOrCreate(
    //                     ['user_id' => auth()->id()],
    //                     ['token' => $request->device_token]
    //                 );
    //             }
    //             $tokenIds = DeviceToken::select('id')->where('user_id', auth()->user()->id)->get()->toArray();
    //             $tokenArray = [];
    //             foreach ($tokenIds as $key => $value) {
    //                 array_push($tokenArray, $value['id']);
    //             }
    //             User::where('id', auth()->user()->id)->update(['device_token' => $request->device_token]);
    //             User::where('id', auth()->user()->id)->update(['language' => $language]);
    //             return response()->json([
    //                 'status' => true,
    //                 'message' => $language == 'english' ? "Child logged in successfully." : "用户登录成功。",
    //                 'token' => $token,
    //                 'data' => auth()->user(),
    //             ], 200);
    //         } else {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => $language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。',
    //                 'data' => (object) []
    //             ], 200);
    //         }
    //     }
    //     // } catch (\Exception $e) {
    //     //     Log::error($e->getMessage());

    //     //     // Return an error response
    //     //     return response()->json([
    //     //         'data' => (object)[],
    //     //         'status' => false,
    //     //         'message' => 'Failed to login. Please try again later.',
    //     //     ], 201);
    //     // }
    // }

    public function login(Request $request)
    {
        // try {
        $language = $request->language ?? 'english';

        if ($request->type == 'parent') {
            $typeId = Role::where('name', $request->type)->value('id');
            $user_id = User::where('country_code', $request->country_code)->where('phone_no', $request->phone_no)->where('user_role_id', $typeId)->whereNull('school_id')->first();

            if (empty($request->phone_no)) {
                return ApiResponse::error($language == 'english' ? "The phone_no field is required." : "phone_no 字段是必填项。", 200, null, (object) []);
            }
            if (empty($user_id)) {
                return ApiResponse::error($language == 'english' ? "User doesn't exist." : "用户不存在。", 200, null, (object) []);
            }
            $input = [
                'phone_no' => $request->phone_no,
                'password' => $request->password,
                'user_role_id' => $typeId,
            ];
            $validate_data = [
                'phone_no' => 'required',
                'password' => ['required'],
            ];
            $custom_messages = [
                'password.required' => $language == 'english' ? 'The password field is required.' : '密码字段是必填项。',
            ];
            $validator = Validator::make($input, $validate_data, $custom_messages, [], ['phone_no' => 'numeric']);

            if ($validator->fails()) {
                return ApiResponse::error($validator->errors()->first(), 200, null, (object) []);
            }
            $credentials = auth()->attempt($input);

            if (empty($credentials)) {
                return ApiResponse::error($language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。', 200, null, (object) []);
            } elseif ($credentials && auth()->user()->status == 'inactive') {
                return ApiResponse::error($language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐号已停用。请联系技术支持。', 200, null, (object) []);
            } elseif ($credentials) {
                if (auth()->user()->is_mobile_verified == 'no') {
                    $typeId = Role::where('name', $request->type)->value('id');
                    $data = User::where('phone_no', $request->phone_no)->where('user_role_id', $typeId)->first();
                    $otp = ___otp_code();
                    $message = "Hello User, Your mobile verification code is $otp";
                    ___sms_sender($message, $data->country_code . $data->phone_no, $language);
                    User::where('id', $data->id)->update(['mobile_otp' => $otp]);
                    return ApiResponse::error($language == 'english' ? 'Please verify your mobile number ' : '请验证您的手机号码', 200, null, (object) []);
                } elseif (empty(auth()->user()->email_verified_at)) {
                    $encryptedEmail = Crypt::encryptString(auth()->user()->email);

                    $emailData = [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'link' => route('user.verify', ['user_id' => auth()->user()->id, 'email' => $encryptedEmail])
                    ];
                    ___mail_sender($emailData['email'], 'verification_email', $emailData, $language);
                    return ApiResponse::error($language == 'english' ? 'Please verify your email ' : '请验证您的电子邮件 ', 200, null, (object) []);
                }
                $token = $this->issueToken(auth()->user(), $request->device_token, $language);
                return ApiResponse::success(auth()->user(), $language == 'english' ? "User logged in successfully." : "用户登录成功。", 200, [], ['token' => $token]);
            } else {
                return ApiResponse::error($language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。', 200, null, (object) []);
            }
        } elseif ($request->type == 'child') {
            $user_id = User::where('user_role_id', 4)->first();

            if (empty($user_id)) {
                return ApiResponse::error($language == 'english' ? "User doesn't exist." : "用户不存在。", 200, null, (object) []);
            }
            $input = [
                'username' => $request->username,
                'password' => $request->password,
                'user_role_id' => 4,
            ];
            $validate_data = [
                'username' => 'required',
                'password' => ['required'],
            ];
            $custom_messages = [
                'username.required' => $language == 'english' ? 'The username field is required.' : '密码字段是必填项。',
            ];
            $validator = Validator::make($input, $validate_data, $custom_messages, [], ['username' => 'string']);

            if ($validator->fails()) {
                return ApiResponse::error($validator->errors()->first(), 200, null, (object) []);
            }
            $credentials = auth()->attempt($input);

            if (empty($credentials)) {
                return ApiResponse::error($language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。', 200, null, (object) []);
            } elseif ($credentials && auth()->user()->status == 'inactive') {
                return ApiResponse::error($language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐号已停用。请联系技术支持。', 200, null, (object) []);
            } elseif ($credentials) {
                if (!empty(auth()->user()->dob)) {
                    try {
                        $dob = Carbon::createFromFormat('Y-m-d', auth()->user()->dob . '-01');
                        \Log::info('DOB check — name: ' . auth()->user()->name . ' | dob: ' . auth()->user()->dob . ' | age: ' . $dob->age);

                        if ($dob->age >= 18) {
                            auth()->logout();
                            return ApiResponse::error($language == 'english' ? 'Account deactivated due to age limit.' : '账户因年龄限制已停用。', 200, null, (object) []);
                        }
                    } catch (\Exception $e) {
                        \Log::error('DOB Parse Error: ' . $e->getMessage());
                    }
                }

                $imagePath = null;
                if ($request->hasFile('image')) {
                    $imagePath = $request->file('image')->store('children', 'public');
                }
                $token = $this->issueToken(auth()->user(), $request->device_token, $language);
                return ApiResponse::success(auth()->user(), $language == 'english' ? "Child logged in successfully." : "用户登录成功。", 200, [], ['token' => $token]);
            } else {
                return ApiResponse::error($language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。', 200, null, (object) []);
            }

        } elseif ($request->type == 'teacher') {

            // Check if the teacher role setup exists
            $user_exists = User::where('user_role_id', 5)->first();

            if (empty($user_exists)) {
                return ApiResponse::error($language == 'english' ? "User doesn't exist." : "用户不存在。", 200, null, (object) []);
            }

            $input = [
                'username'     => $request->username,
                'password'     => $request->password,
                'user_role_id' => 5,
            ];

            $validate_data = [
                'username' => 'required',
                'password' => ['required'],
            ];

            $custom_messages = [
                'username.required' => $language == 'english' ? 'The username field is required.' : '用户名字段是必填项。',
            ];

            $validator = Validator::make($input, $validate_data, $custom_messages, [], ['username' => 'string']);

            if ($validator->fails()) {
                return ApiResponse::error($validator->errors()->first(), 200, null, (object) []);
            }

            // Attempt authentication using the full credential set, including
            // user_role_id, so only teachers can authenticate on this endpoint.
            $credentials = auth()->attempt($input);

            if (empty($credentials)) {
                return ApiResponse::error($language == 'english' ? 'Invalid login credentials.' : '登录凭证无效。', 200, null, (object) []);
            }

            if (auth()->user()->status == 'inactive') {
                auth()->logout();
                return ApiResponse::error($language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐号已停用。请联系技术支持。', 200, null, (object) []);
            }

            if (!empty(auth()->user()->school_id)) {
                $schoolStatus = \DB::table('schools')->where('id', auth()->user()->school_id)->value('status');

                if ($schoolStatus === 'inactive') {
                    auth()->logout();
                    return ApiResponse::error($language == 'english' ? 'Your school account has been deactivated. Please contact your administration.' : '您的学校账户已停用。请联系学校管理员。', 200, null, (object) []);
                }
            }

            // Complete success pipeline: Tokenize and track mobile notifications
            $token = $this->issueToken(auth()->user(), $request->device_token, $language);

            return ApiResponse::success(
                auth()->user(),
                $language == 'english' ? "Teacher logged in successfully." : "教师登录成功。",
                200,
                [],
                ['token' => $token]
            );
        }

        return ApiResponse::error($language == 'english'
                ? 'The type field is required. Use parent, child, or teacher.'
                : 'type 字段是必填项。请使用 parent、child 或 teacher。', 200, null, (object) []);
        // } catch (\Exception $e) { ... }
    }

    // public function forgotPassword(Request $request)
    // {
    //     try {
    //         $typeId = Role::where('name', $request->type)->value('id');
    //         $language = $request->language;
    //         $validator  =  Validator::make($request->all(), [
    //             'email' => 'required|regex:/\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})/',
    //         ], [
    //             'email.required' => $language == 'english' ? 'The email field is required.' : '电子邮件字段是必需的。',
    //             'email.regex' => $language == 'english' ? 'Invalid email format' : '电子邮件格式无效',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
    //         }
    //         $typeId = Role::where('name', $request->type)->value('id');
    //         $user = User::where('email', $request->email)->where('user_role_id', $typeId)->first();
    //         $useremail = User::where('email', $request->email)->first();
    //         if (!empty($user) && $user['status'] == 'inactive') {
    //             return response()->json(['status' => false, 'message' => $language == 'english' ? 'This account has been deactivated. Please contact technical support.' : '该帐户已被停用。请联系技术支持。'], 404);
    //         }
    //         if (empty($useremail)) {
    //             return response()->json(['status' => false, 'message' => $language == 'english' ? 'This e-mail is not registered.' : '该电子邮件未注册。'], 404);
    //         }
    //         if (!empty($user) && $typeId == '2' && $user['is_approved'] == 'no') {
    //             return response()->json(['status' => false, 'message' => $language == 'english' ? 'This account is still under review and verification. Thank you for your patience.' : '该账户仍在审核和验证中。感谢您的耐心等待。'], 404);
    //         }
    //         if (!empty($user) && $user['is_mobile_verified'] == 'no') {
    //             return response()->json(['status' => false, 'message' => $language == 'english' ? 'You must verify your phone number before proceeding with your registration.' : '在继续注册之前，您必须验证您的电话号码。'], 404);
    //         }
    //         if (empty($user)) {

    //             return response()->json(['success' => false, 'message' => $language == 'english' ? 'This e-mail is not registered.' : '该电子邮件未注册。'], 404);
    //         }
    //         $name = $user->name;
    //         $otp = mt_rand(1000, 9999);
    //         $user = User::where('email', $request->email)->update(['otp' => $otp]);
    //         $success = [
    //             'name' => $name,
    //             'email' => $request['email'],
    //             'otp' => $otp,

    //         ];
    //         ___mail_sender($request['email'],'otp',$success,$language);

    //         return response()->json(['success' => true, 'message' => $language == 'english' ? 'OTP sent successfully.' : 'OTP 发送成功。', 'data' => $request['email']], 200);
    //     } catch (\Exception $e) {
    //         Log::error($e->getMessage());

    //         // Return an error response
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Failed to forgot your password. Please try again later.',
    //         ], 500);
    //     }
    // }






    public function verifyOtp(Request $request)
    {
        // try {
        $language = $request->language;
        $validator  =  Validator::make($request->all(), [
            'otp' => 'required',
        ], [
            'otp.required' => $language == 'english' ? 'Please Enter OTP.' : '请输入一次性密码。'
        ]);
        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422, null, null, ['success' => false]);
        }
        $userOtp = $request->otp;
        $userPhone = $request->phone_no;
        // if ($request->verification_type == 'register') {
        $typeId = Role::where('name', $request->type)->value('id');
        $user = User::where('phone_no', $userPhone)->where('user_role_id', 3)->latest()->first();
        // dd($user);

        if (!$user) {
            return ApiResponse::error($language == 'english' ? 'OTP does not match.' : 'OTP 不匹配。', 200);
        }

        $otp = $user->otp;
        if ($otp !== null && (string) $otp === (string) $userOtp) {
            if ($request->phone_no && $request->country_code) {
                User::where('country_code', $request->country_code)->where('phone_no', $userPhone)->where('user_role_id', 3)->update([
                    'is_mobile_verified' => 'yes',
                    // 'secondary_mobile_verified' => 'yes',
                ]);
                $user_data = User::where('country_code', $request->country_code)->where('phone_no', $request->phone_no)->where('user_role_id', $typeId)->latest()->first();
            }
            return ApiResponse::success(null, $language == 'english' ? 'OTP verified succesfully.' : 'OTP 验证成功。', 200);
        } else {
            return ApiResponse::error($language == 'english' ? 'OTP does not match.' : 'OTP 不匹配。', 200);
        }
        // } catch (\Exception $e) {
        //     Log::error($e->getMessage());

        //     // Return an error response
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Failed to verify OTP. Please try again later.',
        //     ], 200);
        // }
    }

    public function studentLogin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $language = "english";
            $user = User::where('email', $request->email)->where('user_type', 'parent')->first();

            // $school = School::where('school_code', $request->school_code)->first();
            $school = School::where('id', $user->school_id)->first();

            if (!$school) {
                return ApiResponse::error($language == 'english' ? 'School code mismatch' : '学校代码不匹配', 200);
            }
            if (!$user || !Hash::check($request->password, $user->password)) {
                return ApiResponse::error($language == 'english' ? 'Invalid credentials' : '凭证无效', 200);
            }


            if (!empty($request->device_token)) {
                DeviceToken::updateOrCreate(
                    ['user_id' => $user->id, 'token' => $request->device_token],
                    ['token' => $request->device_token]
                );
            }


            $token = $user->createToken('authToken')->accessToken;
            $language = "english";
            return ApiResponse::success(
                $user,
                $language == 'english' ? 'User logged in successfully.' : '用户登录成功。',
                200,
                ['user' => $user],
                ['token' => $token]
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $language = "english";
            return ApiResponse::error($language == 'english' ? 'Failed to login. Please try again later.' : '登录失败。请稍后重试。', 200, null, (object)[]);
        }
    }

    public function individualLogin(Request $request)
    {
        // Resolved before the try: the catch below reads $language, and a
        // validation failure used to reach it before it was ever assigned,
        // turning every bad login payload into a 500.
        $language = $request->language ?? 'english';

        try {
            $request->validate([
                'country_code' => 'required',
                'phone_no' => 'required',
                'password' => 'required',
            ]);
            $user = User::where('country_code', $request->country_code)->where('phone_no', $request->phone_no)->where('user_type', 'parent')->where('deleted_at', NULL)->first();
            $deleted_user = User::where('country_code', $request->country_code)->where('phone_no', $request->phone_no)->where('user_type', 'parent')->whereNot('deleted_at', NULL)->first();
            if ($deleted_user) {
                return ApiResponse::error($language == 'english' ? 'Your account has been deleted.' : '您的帐户已被删除。', 401);
            }
            if (!$user || !Hash::check($request->password, $user->password)) {
                return ApiResponse::error($language == 'english' ? 'Invalid credentials' : '凭证无效', 401);
            }

            if (!empty($request->device_token)) {
                DeviceToken::updateOrCreate(
                    ['user_id' => $user->id, 'token' => $request->device_token],
                    ['token' => $request->device_token]
                );
            }
            $tokenName = $request->remember_me ? 'authTokenRemember' : 'authToken';
            $token = $user->createToken($tokenName, [], $request->remember_me ? now()->addMonths(6) : now()->addDays(1))->accessToken;
            return ApiResponse::success(
                $user,
                $language == 'english' ? 'User logged in successfully.' : '用户登录成功。',
                200,
                ['user' => $user],
                ['token' => $token]
            );
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error($language == 'english' ? 'Failed to login. Please try again later.' : '登录失败。请稍后重试。', 201, null, (object)[]);
        }
    }


    public function reset(Request $request)
    {
        $language = $request->language ?? 'english';
        if ($request->user_type == 'parent') {

            $messages = [
                'old_password.required' => $language == 'english' ? 'The old password field is required.' : '旧密码字段是必需的。',
                'new_password.required' => $language == 'english' ? 'The new password field is required.' : '新密码字段是必需的。',
                'new_password.min' => $language == 'english' ? 'The new password must be at least 8 characters.' : '新密码必须至少包含 8 个字符。',
                'new_password.regex' => $language == 'english'
                    ? 'Password must contain at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.'
                    : '密码必须至少包含 1 个大写字母、1 个小写字母、1 个数字和 1 个特殊字符。',
                'confirm_new_password.required' => $language == 'english' ? 'The confirm password field is required.' : '确认密码字段是必需的。',
                'confirm_new_password.same' => $language == 'english' ? 'Confirm password must match the new password.' : '确认密码必须与新密码匹配。',
            ];

            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => [
                    'required',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
                ],
                'confirm_new_password' => 'required|same:new_password',
            ], $messages);

            if ($validator->fails()) {
                return ApiResponse::error($validator->errors()->first(), 422, null, (object)[]);
            }

            $user = auth()->user();
            if (!$user || !Hash::check($request->old_password, $user->password)) {
                return ApiResponse::error($language == 'english' ? 'Old password is incorrect.' : '旧密码不正确。', 200, null, (object)[]);
            }
            if (Hash::check($request->new_password, $user->password)) {
                return ApiResponse::error($language == 'english'
                        ? 'New password cannot be the same as the old password.'
                        : '新密码不能与旧密码相同。', 200, null, (object)[]);
            }
            $user->password = bcrypt($request->new_password);
            $user->is_first_login = 'yes';
            $user->save();
            // $user->token()->revoke();
            if ($user->token()) {
                $user->token()->revoke();
            }
        } else {
            $messages = [
                'new_password.required' => $language == 'english' ? 'The new password field is required.' : '新密码字段是必需的。',
                'new_password.min' => $language == 'english' ? 'The new password must be at least 8 characters.' : '新密码必须至少包含 8 个字符。',
                'new_password.regex' => $language == 'english'
                    ? 'Password must contain at least 1 uppercase, 1 lowercase, 1 number, and 1 special character.'
                    : '密码必须至少包含 1 个大写字母、1 个小写字母、1 个数字和 1 个特殊字符。',
                'confirm_new_password.required' => $language == 'english' ? 'The confirm password field is required.' : '确认密码字段是必需的。',
                'confirm_new_password.same' => $language == 'english' ? 'Confirm password must match the new password.' : '确认密码必须与新密码匹配。',
            ];

            $validator = Validator::make($request->all(), [
                'new_password' => [
                    'required',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
                ],
                'confirm_new_password' => 'required|same:new_password',
            ], $messages);

            if ($validator->fails()) {
                return ApiResponse::error($validator->errors()->first(), 422, null, (object)[]);
            }
            $auth_user = auth()->user();
            $child_user = $request->filled('user_id')
                ? User::find($request->user_id)
                : $auth_user;

            // A caller may only reset their own password, or that of their own
            // child. Without this the body-supplied user_id targeted any account.
            $isSelf = $child_user && $auth_user && $child_user->id === $auth_user->id;
            $isOwnChild = $child_user && $auth_user
                && $auth_user->user_type === 'parent'
                && (int) $child_user->parent_id === (int) $auth_user->id;

            if (!$child_user || (!$isSelf && !$isOwnChild)) {
                return ApiResponse::error($language == 'english'
                        ? 'You are not allowed to change this password.'
                        : '您无权更改此密码。', 200, null, (object)[]);
            }

            if (Hash::check($request->new_password, $child_user->password)) {
                return ApiResponse::error($language == 'english'
                        ? 'New password cannot be the same as the old password.'
                        : '新密码不能与旧密码相同。', 200, null, (object)[]);
            }
            if ($auth_user->user_type == 'parent') {
                $child_user->is_first_login = 'yes';
                if ($child_user->token()) {
                    $child_user->token()->revoke();
                }

                Token::where('user_id', $child_user->id)->update(['revoked' => true]);
            } else {
                $child_user->is_first_login = 'no';
            }

            $child_user->password = bcrypt($request->new_password);
            $child_user->save();
        }


        return ApiResponse::success((object)[], $language == 'english' ? 'Password changed successfully.' : '密码修改成功。', 200);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        // delete user notifications
        DeviceToken::where('user_id', $user->id)->delete();
        $language = $request->language;

        // token() is null when the request was not authenticated through a
        // stored Passport token; reset() already guards this the same way.
        if ($user->token()) {
            $user->token()->revoke();
        }
        return [
            "status" => true,
            "message" => $language == 'english' ? "Logout Successfully." : "登出成功。",
            'user' => $user,
        ];
    }

    public function deleteUser(Request $request)
    {
        $user = User::find(auth()->user()->id);
        $language = $user->language;
        if ($user) {
            $children = Child::where('parent_id', $user->id)->get();
            foreach ($children as $child) {
                $child->delete();
            }
            $emailData = [
                'name' => $user->name,
                'email' => $user->email,
            ];
            ___mail_sender($user->email, 'delete_account', $emailData, $language);
            DeviceToken::where('user_id', $user->id)->delete();
            $user->delete(); // Soft delete the parent account

            return [
                'status' => true,
                'message' => $language == 'english' ? 'Your account has been deleted successfully.' : '您的帐户已成功删除。',
            ];
        } else {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'User not found.' : '未找到用户。',
            ];
        }
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        $token = DB::table('password_reset_tokens')->where('token', $request->token)->first();

        if (!$token) {
            return redirect('reset-password-message')->with('invalid', 'Reset Password link is invalid.');
        }

        // Previously fell off the end and returned null when the token was
        // valid but the two passwords differed.
        if ($request->password !== $request->confirm_password) {
            return redirect('reset-password-message')->with('invalid', 'The new password and confirm password must be the same.');
        }

        User::where('email', $token->email)->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('token', $request->token)->delete();

        return redirect('reset-password-message')->with('success', 'Password updated successfully.');
    }

    public function forgotPassword(Request $request)
    {
        $language = $request->language;
        $request->validate([
            'email' => 'required|regex:/(.+)@(.+)\.(.+)/i|',
        ]);

        $mail = $request->email;
        $user = User::where('email', $mail)->first();
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
            ___mail_sender($mail, 'forgot_password', $email, $language);
            return [
                'status' => true,
                'message' => $language == 'english' ? 'Password reset link send to your entered email' : '密码重置链接发送至您输入的电子邮箱',
            ];
        } else {
            return [
                'status' => false,
                'message' => $language == 'english' ? "Email doesn't Exists." : '您的帐户已成功删除。',
            ];
        }
    }

    public function resetPasswordPage(Request $request, $token)
    {
        $user = User::where('password_reset_code', $token)->first();

        if ($user && now() <= $user->password_reset_expires_at) {
            $decryptedPassword = $user->password;
            // dd($decryptedPassword);
            return view('admin.resetPassword', compact('token', 'decryptedPassword'));
        } else {
            return view('admin.resetExpire', compact('token'));
        }
    }

    public function passwordReset(Request $request, $token)
    {
        // $language was referenced by both responses below but never assigned,
        // so a successful reset raised ErrorException instead of answering.
        $language = $request->language ?? 'english';

        $validator = Validator::make($request->all(), [
            // 'password' => 'required|string|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            // 'confirm_password' => 'required|string|min:8|same:password|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            'old_password' => 'required',
            'password' => 'required|min:8|max:15|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9]).{8,15}$/',
            'confirm_password' => 'required|same:password',
        ], [
            // 'password.required' => 'The new password field is required',
            // 'confirm_password.required' => 'The confirm password field is required',
            // 'password.regex' => 'The new password must contain 1 Upper Case,1 Lower Case, 1 Numeric and 1 Special Character.',
            // 'confirm_password.regex' => 'The confirm password must contain 1 Upper Case,1 Lower Case, 1 Numeric and 1 Special Character.',
            // 'confirm_password.same' => 'The new password and confirm Password should be same.',|
            'old_password.required' => "Old Password is required.",
            'password.required' => "New Password is required.",
            'confirm_password.required' => "Confirm Password is required.",
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

        if (!$user) {
            return ApiResponse::error(
                $language == 'english' ? 'Reset password link is invalid or has expired.' : '重置密码链接无效或已过期。',
                200
            );
        }

        if ($request->password == $request->confirm_password) {
            $user->password = Hash::make($request->password);
            $user->password_reset_code = null;
            $user->save();
            return ApiResponse::success(null, $language == 'english' ? ' Password changed successfully. Login again to continue using app' : '密码更改成功。请重新登录以继续使用应用程序', 200);
        } else {
            return ApiResponse::error($language == 'english' ? "Passwords doesn't match." : "密码不匹配。", 200);
        }
    }

    // public function updateParentProfile(Request $request)
    // {
    //     $language = $request->language ?? 'english';
    //     $user = User::find(auth()->user()->id);

    //     if ($user) {
    //         $user->update([
    //             'name' => $request->name,
    //         ]);
    //         return response()->json([
    //             'status' => true,
    //             'message' => $language == 'english' ? "Profile Updated Successfully!" : "密码不匹配。"
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'english' ? "User not found" : "密码不匹配。"
    //         ], 200);
    //     }
    // }
    public function updateParentProfile(Request $request)
    {
        $language = $request->language ?? 'english';

        $user = User::find(auth()->user()->id);

        if (!$user) {
            return ApiResponse::error($language == 'english'
                    ? 'The school code you entered is not valid. Please check and try again.'
                    : '您输入的学校代码无效，请检查后再试。', 200);
        }
        $updateData = [
            'name' => $request->name,
        ];
        if ($request->filled('school_code')) {
            $school = School::where('school_code', $request->school_code)
                ->where('status', 'active')
                ->first();
            if (!$school) {
                return ApiResponse::error($language === 'english'
                        ? 'Invalid school code. Please check and try again.'
                        : '学校代码无效，请检查后再试。', 422);
            }
            $updateData['school_id'] = $school->id;
        }
        $user->update($updateData);
        return ApiResponse::success(null, $language === 'english'
                ? 'Profile updated successfully!'
                : '个人资料更新成功！', 200);
    }

    // public function getChildProfile(Request $request)
    // {
    //     $id = $request->child_id;
    //     $details = User::where('id', $id)->where('status', 'active')->first();
    //     $details->image = $details->image ? getImagePathUrl($details->image, 'assets/avtar') : null;
    //     $details->avtar_image = $details->avtar_image ? getImagePathUrl($details->avtar_image, 'assets/avtar') : null;
    //     if ($details) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => $request->language == 'english' ? "Details fetched successfully!" : "详细信息获取成功！",
    //             'data' => $details
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $request->language == 'english' ? "Child details not found" : "未找到儿童详细信息",
    //             'data' => null
    //         ], 200);
    //     }
    // }

    public function getChildProfile(Request $request)
    {
        // child_id used to read any user row by id.
        $target = $this->resolveTargetUser($request, 'child_id');

        if (!$target) {
            return $this->unauthorisedTargetResponse($request->language ?? 'english');
        }

        $details = User::where('id', $target->id)
            ->where('status', 'active')
            ->first();

        if ($details) {

            $details->image = $details->image
                ? getImagePathUrl($details->image, 'assets/avtar')
                : null;

            $details->avtar_image = $details->avtar_image
                ? getImagePathUrl($details->avtar_image, 'assets/avtar')
                : null;

            $details->is_account_active = false;

            if (!empty($details->dob)) {

                try {

                    $dob = Carbon::createFromFormat('Y-m', $details->dob);

                    $details->is_account_active = $dob->age < 18;

                } catch (\Exception $e) {

                    \Log::error('DOB Parse Error: ' . $e->getMessage());
                }
            }

            return ApiResponse::success($details, $request->language == 'english'
                    ? "Details fetched successfully!"
                    : "详细信息获取成功！", 200);

        } else {

            // Left hand-rolled: ApiResponse renders a null data payload as {},
            // and this endpoint's contract is data: null.
            return response()->json([
                'status' => false,
                'message' => $request->language == 'english'
                    ? "Child details not found"
                    : "未找到儿童详细信息",
                'data' => null
            ], 200);
        }
    }

    // public function updateProfileImage(Request $request)
    // {
    //     if ($request->is_avatar_primary == 'yes') {
    //         User::where('id', auth()->user()->id)->where('status', 'active')->update(['is_avatar_primary' => 'yes']);
    //     } else {
    //         $imagePath = null;
    //         if ($request->hasFile('profile_image')) {
    //             $file = $request->file('profile_image');
    //             $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //             $imagePath = asset('assets/avtar/' . $imageName);
    //             $file->move(public_path('assets/avtar'), $imageName);
    //         }
    //         User::where('id', auth()->user()->id)->where('status', 'active')
    //             ->update([
    //                 'is_avatar_primary' => 'no',
    //                 'image' => $imagePath,
    //             ]);
    //     }
    //     $user_details =  User::where('id', auth()->user()->id)->first();


    //     return response()->json([
    //         'status' => true,
    //         'message' => $request->language == 'english' ? "Profile image updated successfully!" : "头像更新成功！",
    //         'data' => $user_details
    //     ], 200);
    //     // }else{
    //     //     return response()->json([
    //     //         'status' => false,
    //     //         'message' => $request->language == 'english' ? "User details not found" : "未找到用户详细信息",
    //     //         'data' => null
    //     //     ], 200);
    //     // }
    // }

    public function updateProfileImage(Request $request)
    {
        $user = User::where('id', auth()->user()->id)->where('status', 'active')->first();
        if (!$user) {
            // Left hand-rolled: ApiResponse renders a null data payload as {},
            // and this endpoint's contract is data: null.
            return response()->json([
                'status' => false,
                'message' => $request->language == 'english' ? "User details not found" : "未找到用户详细信息",
                'data' => null
            ], 200);
        }

        if ($request->has('is_avatar_primary')) {
            $user->is_avatar_primary = $request->is_avatar_primary;
        }

        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');

            if ($user->image) {
                $oldPath = 'assets/avtar/' . $user->image;
                if (Storage::disk('s3')->exists($oldPath)) {
                    Storage::disk('s3')->delete($oldPath);
                }
            }

            $imageName = uploadFile($file, 'assets/avtar', $user->image);
            if (!$imageName) {
                return response()->json([
                    'status' => false,
                    'message' => $request->language == 'english' ? 'Failed to upload image.' : '上传图片失败。',
                    'data' => null
                ], 200);
            }

            $user->image = $imageName;
        }

        $user->save();

        $user_details = $user->fresh();
        if ($user_details->image) {
            $user_details->image = getImagePathUrl($user_details->image, 'assets/avtar');
        }

        return ApiResponse::success($user_details, $request->language == 'english' ? "Profile updated successfully!" : "头像更新成功！", 200);
    }

    /**
     * Update Teacher Account and Professional Profile Details
     * Implemented using isolated DB Transactions for atomic runtime safety.
     */
    public function updateTeacherProfile(UpdateTeacherProfileRequest $request)
    {
        $language = $request->language ?? 'english';

        $user = User::find(auth()->id());

        if (!$user) {
            return ApiResponse::error($language == 'english' ? "User profile not found." : "找不到用户个人资料。", 200, null, (object) []);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'name'         => $request->name,
                'username'     => $request->username,
                'email'        => $request->email,
                'country_code' => $request->country_code,
                'phone_no'     => $request->phone_no,
                'language'     => $language
            ]);

            // Experience handles 4.5, 5, etc., while subjects/qualifications automatically cast to arrays
            $user->teacherProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'experience'     => $request->experience,
                    'subjects'       => $request->subject,
                    'qualifications' => $request->qualification,
                ]
            );

            DB::commit();

            $user->load('teacherProfile');

            return ApiResponse::success($user, $language === 'english' ? 'Profile updated successfully!' : '个人资料更新成功！', 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            // \Log::error('Teacher Profile Update Exception: ' . $e->getMessage());

            return ApiResponse::error($language === 'english' ? 'Failed to update profile. Please try again later.' : '更新个人资料失败。请稍后再试。', 200, null, (object) []);
        }
    }
}
