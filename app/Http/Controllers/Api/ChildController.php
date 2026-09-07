<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiHitLog;
use App\Models\Child;
use App\Models\User;
use App\Models\AvatarImage;
use App\Models\BatteryEvent;
use App\Models\BatterySetting;
use App\Models\UserAvatarImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\VideoContent;
use App\Models\Category;
use App\Models\ChildMood;
use App\Models\DeviceToken;
use App\Models\Mood;
use App\Models\School;
use App\Models\UserContentWatchHistory;
use Illuminate\Validation\Rule;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class ChildController extends Controller
{
    public function addChild(Request $request)
    {
        // try {
        $user = User::where('id', Auth::id())->first();
        $language = $request->language;

        $messages = $language == 'english' ? [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a valid string.',
            'dob.required'  => 'The birth year is required.',
            'dob.date_format' => 'The birth date must be in YYYY-MM format.',
            'image.image' => 'The image must be a valid image file.',
            'image.mimes' => 'The image must be in jpeg, png, jpg, or gif format.',
            'image.max' => 'The image size must not exceed 2MB.',
            'name.min' => 'The name field must not be less than 3 characters.',
            'name.max' => 'The name field must not be greater than 50 characters.',
        ] : [
            'name.required' => '名称字段是必需的。',
            'name.min' => '名称字段不得少于3个字符。',
            'name.max' => '名称字段不得超过 50 个字符。',
            'name.string' => '名称必须是有效的字符串。',
            'dob.required'  => '出生年份是必填项。',
            'dob.date_format' => '出生日期格式必须为 YYYY-MM。',
            'image.image' => '图片必须是有效的图片文件。',
            'image.mimes' => '图片必须是jpeg、png、jpg或gif格式。',
            'image.max' => '图片大小不得超过2MB。',
        ];
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50',
            'dob' => 'required|date_format:Y-m',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'username' => [
                'required',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'data' => (object)[],
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 201);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('children', 'public');
        }
        $data['password'] = Hash::make($request->password);

        $birthDate = $request->dob;
        $child = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'dob' => $birthDate,
            // 'profile_image' => $imagePath ?? null,
            'parent_id' => Auth::id(),
            // 'loyalty_points' => 100,
            'battery_points' => 100,
            'user_role_id' => 4,
            'user_type' => 'child',
            'password' => $data['password'],
            'is_first_login' => 'yes',
            'is_avatar_primary' => 'no',
        ]);
        BatteryEvent::create([
            'user_id' => $child->id,
            'direction' => 'credit',
            'points' => $child->battery_points,
            'reason' => 'Initial default battery points',
            'effective_date' => now(),
        ]);

        /** 🔔 Send notification of 100% battery */
        $content = getNotificationContent('child_add', [
            'child_name'      => $request->username,
            'battery_points'  => $child->battery_points,
        ]);

        $notification_type = "child_add";
        $extra_data = [
            "child_id"       => $child->id,
            "battery_points" => $child->battery_points,
            "type"           => "child_add"
        ];
        $user_type = "user";

        // Parent ka device token
        $checkUser = User::where('id', $user->id)
            ->first();

        if ($checkUser) {
            $device = DeviceToken::where('user_id', $user->id)
                ->whereNotNull('token')
                ->first();

            if ($device) {
                sendNotificationSender(
                    $device->user_id,
                    $content['title'],
                    $content['body'],
                    $notification_type,
                    $extra_data,
                    $user_type
                );
            }
        }

        return response()->json([
            'status' => true,
            'message' => $language == 'english' ? 'Child added successfully' : '孩子添加成功',
            'child' => $child
        ], 201);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'data' => (object)[],
        //         'status' => false,
        //         'message' => $language == 'english' ? 'Failed to update child' : '无法更新子项',
        //     ], 201);
        // }
    }

    public function editChild(Request $request)
    {
        try {
            $user = User::where('id', Auth::id())->first();
            $language = 'english';

            $messages = $language == 'english' ? [
                'name.required' => 'The name field is required.',
                'name.string' => 'The name must be a valid string.',
                'dob.required'  => 'The birth year is required.',
                'dob.date_format' => 'The birth date must be in YYYY-MM format.',
                'image.image' => 'The image must be a valid image file.',
                'image.mimes' => 'The image must be in jpeg, png, jpg, or gif format.',
                'image.max' => 'The image size must not exceed 2MB.',
                'name.min' => 'The name field must not be less than 3 characters.',
                'name.max' => 'The name field must not be greater than 50 characters.',
            ] : [
                'name.required' => '名称字段是必需的。',
                'name.string' => '名称必须是有效的字符串。',
                'dob.required'  => '出生年份是必填项。',
                'dob.date_format' => '出生日期格式必须为 YYYY-MM。',
                'image.image' => '图片必须是有效的图片文件。',
                'image.mimes' => '图片必须是jpeg、png、jpg或gif格式。',
                'image.max' => '图片大小不得超过2MB。',
                'name.min' => '名称字段不得少于3个字符。',
                'name.max' => '名称字段不得超过 50 个字符。'
            ];
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:50',
                'dob' => 'required|date_format:Y-m',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ], $messages);

            if ($validator->fails()) {
                return response()->json([
                    'data' => (object)[],
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ], 201);
            }
            // $child = Child::where('id', $request->id)->where('parent_id', Auth::id())->first();
            $child = user::where('id', $request->id)->where('parent_id', Auth::id())->first();

            if (!$child) {
                return response()->json([
                    'status' => false,
                    'message' => $language == 'english' ? 'Child not found or unauthorized access' : '未找到孩子或未经授权的访问',
                ], 404);
            }
            // if ($request->hasFile('image')) {
            //     if ($child->image) {
            //         Storage::disk('public')->delete($child->image);
            //     }
            //     $child->image = $request->file('image')->store('children', 'public');
            // }
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Delete old image from S3 if exists
                if ($child->image) {
                    $oldPath = 'children/' . $child->image;
                    if (Storage::disk('s3')->exists($oldPath)) {
                        Storage::disk('s3')->delete($oldPath);
                    }
                }
                $imageName = uploadFile($file, 'children');
                $child->image = $imageName;
            }
            $birthDate = $request->dob;

            $child->name = $request->name;
            $child->dob = $birthDate;
            $child->save();
            return response()->json([
                'status' => true,
                'message' => $language == 'english' ? 'Child updated successfully' : '子项更新成功',
                'child' => $child
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $language == 'english' ? 'Failed to update child' : '无法更新子项',
            ], 500);
        }
    }

    // public function getProfile(Request $request){
    //     $user = User::where('id', Auth::id())
    //     ->with('children')
    //     ->first();
    //     $language = $user->language;
    //     if ($user && $user->children) {
    //         foreach ($user->children as $child) {
    //             $child->age = now()->year - date('Y', strtotime($child->dob));
    //         }
    //     }
    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'english' ?'User not found':'未找到用户',
    //             'data' => (object)[]
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' =>  $language == 'english' ? "User Profile Fetched Successfully" : '已成功获取用户个人资料',
    //         'data' => $user
    //     ], 200);
    // }

    // public function getProfile(Request $request)
    // {
    //     $user = User::where('id', Auth::id())
    //         ->with('userAvatar') // Load the child's avatar relation
    //         ->first();

    //     $language = $user->language ?? 'english'; // Handle null cases

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'english' ? 'User not found' : '未找到用户',
    //             'data' => (object)[]
    //         ], 404);
    //     }

    //     // if ($user->children) {
    //     // foreach ($user->children as $child) {
    //     // Calculate age
    //     $user->age = now()->year - date('Y', strtotime($user->dob));

    //     // Fetch the child's avatar
    //     $childAvatar = $user->userAvatar; // Assuming the relation is `userAvatar`

    //     if ($childAvatar) {
    //         // Fetch avatar parts from AvatarImage table
    //         $avatarParts = AvatarImage::whereIn('id', array_filter([
    //             $childAvatar->expressions_id,
    //             $childAvatar->glasses_id,
    //             $childAvatar->backgrounds_id,
    //             $childAvatar->shoes_id,
    //             $childAvatar->hats_id,
    //             $childAvatar->scarves_id,
    //             $childAvatar->yoga_mat_id,
    //             $childAvatar->bottles_id
    //         ]))->get(['id', 'apply_image', 'preview_image', 'points'])->keyBy('id');

    //         // Attach formatted avatar details to the child object
    //         $user->avatar = [
    //             'id' => $childAvatar->id,
    //             'Expressions' => formatAvatarPart($childAvatar->expressions_id, $avatarParts),
    //             'Glasses' => formatAvatarPart($childAvatar->glasses_id, $avatarParts),
    //             'Backgrounds' => formatAvatarPart($childAvatar->backgrounds_id, $avatarParts),
    //             'Shoes' => formatAvatarPart($childAvatar->shoes_id, $avatarParts),
    //             'Caps' => formatAvatarPart($childAvatar->hats_id, $avatarParts),
    //             'Scarves' => formatAvatarPart($childAvatar->scarves_id, $avatarParts),
    //             'YogaMat' => formatAvatarPart($childAvatar->yoga_mat_id, $avatarParts),
    //             'Bottles' => formatAvatarPart($childAvatar->bottles_id, $avatarParts),
    //             'image' => $childAvatar->image ? asset($childAvatar->image) : null,
    //             'created_at' => $childAvatar->created_at,
    //             'updated_at' => $childAvatar->updated_at
    //         ];
    //     } else {
    //         $user->avatar = null;
    //     }
    //     if ($user->user_role_id === '3') {
    //         $user->is_child_added = User::where('parent_id', $user->id)->exists() ? 'yes' : 'no';

    //         $user->subscription = Subscription::where('user_id', Auth::id())->latest()->first();
    //     } else {
    //         $user_id = $user->parent_id;
    //         $user->subscription = Subscription::where('user_id', $user_id)->latest()->first();
    //     }
    //     // }
    //     // }

    //     $batteryLevel = BatteryEvent::where('user_id', Auth::id())
    //         ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN points ELSE -points END) as balance")
    //         ->value('balance');

    //     $batteryLevel = max(0, $batteryLevel);
    //     $user->battery_points = $batteryLevel;
    //     return response()->json([
    //         'status' => true,
    //         // 'battery_level' => $batteryLevel ?? 0,
    //         'message' => $language == 'english' ? "User Profile Fetched Successfully" : '已成功获取用户个人资料',
    //         'data' => $user
    //     ], 200);
    // }
    // public function getProfile(Request $request)
    // {
    //     $user = User::where('id', Auth::id())
    //         ->with('userAvatar')
    //         ->first();

    //     $language = $user->language ?? 'english';

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language == 'english' ? 'User not found' : '未找到用户',
    //             'data' => (object)[]
    //         ], 404);
    //     }

    //     $todayHits = ApiHitLog::where('user_id', Auth::id())
    //         ->where('api_name', 'getProfile')
    //         ->whereDate('created_at', now()->toDateString())
    //         ->count();

    //     if ($todayHits < 2) {   // ek din me max 2 hi log
    //         ApiHitLog::create([
    //             'user_id' => Auth::id(),
    //             'api_name' => 'getProfile',
    //         ]);

    //         // Last 7 din ke total hits
    //         $hitsThisWeek = ApiHitLog::where('user_id', Auth::id())
    //             ->where('api_name', 'getProfile')
    //             ->where('created_at', '>=', now()->startOfWeek())
    //             ->count();

    //         if ($hitsThisWeek >= 2) {
    //             $loginBatterySetting = BatterySetting::where('option_key', 'login_battery_percentage')->first();

    //             BatteryEvent::create([
    //                 'user_id' => Auth::id(),
    //                 'direction' => 'credit',
    //                 'points' => $loginBatterySetting?->option_value ?? 0,
    //                 'reason' => 'Active profile hits (>=2 this week)',
    //                 'effective_date' => now(),
    //             ]);
    //         } elseif ($hitsThisWeek <= 1) {
    //             $loginBatterySetting = BatterySetting::where('option_key', 'login_battery_percentage_negative')->first();
    //             BatteryEvent::create([
    //                 'user_id' => Auth::id(),
    //                 'direction' => 'debit',
    //                 'points' => $loginBatterySetting?->option_value ?? 0,
    //                 'reason' => 'Inactive profile hits (<=1 this week)',
    //                 'effective_date' => now(),
    //             ]);
    //         }
    //     }

    //     // --- baaki code bilkul same as it is ---
    //     $user->age = now()->year - date('Y', strtotime($user->dob));

    //     $childAvatar = $user->userAvatar;
    //     if ($childAvatar) {
    //         $avatarParts = AvatarImage::whereIn('id', array_filter([
    //             $childAvatar->expressions_id,
    //             $childAvatar->glasses_id,
    //             $childAvatar->backgrounds_id,
    //             $childAvatar->shoes_id,
    //             $childAvatar->hats_id,
    //             $childAvatar->scarves_id,
    //             $childAvatar->yoga_mat_id,
    //             $childAvatar->bottles_id
    //         ]))->get(['id', 'apply_image', 'preview_image', 'points'])->keyBy('id');


    //     $user->avatar = [
    //         'id' => $childAvatar->id,
    //         'Expressions' => formatAvatarPart($childAvatar->expressions_id, $avatarParts),
    //         'Glasses' => formatAvatarPart($childAvatar->glasses_id, $avatarParts),
    //         'Backgrounds' => formatAvatarPart($childAvatar->backgrounds_id, $avatarParts),
    //         'Shoes' => formatAvatarPart($childAvatar->shoes_id, $avatarParts),
    //         'Caps' => formatAvatarPart($childAvatar->hats_id, $avatarParts),
    //         'Scarves' => formatAvatarPart($childAvatar->scarves_id, $avatarParts),
    //         'YogaMat' => formatAvatarPart($childAvatar->yoga_mat_id, $avatarParts),
    //         'Bottles' => formatAvatarPart($childAvatar->bottles_id, $avatarParts),
    //         // 'image' => $childAvatar->image ? asset($childAvatar->image) : null,
    //         'image' => $childAvatar->image ? getImagePathUrl($childAvatar->image, 'assets/avtar') : null,
    //         'created_at' => $childAvatar->created_at,
    //         'updated_at' => $childAvatar->updated_at
    //     ];
    //     // ✅ overwrite userAvatar relation response as well
    //     $childAvatar->image = $childAvatar->image
    //         ? getImagePathUrl($childAvatar->image, 'assets/avtar')
    //         : null;
    //     $user->setRelation('userAvatar', $childAvatar);

    //     $user->image = $user->image ? getImagePathUrl($user->image, 'assets/avtar') : null;
    //     $user->avtar_image = $user->avtar_image ? getImagePathUrl($user->avtar_image, 'assets/avtar') : null;
    // } else {
    //     $user->avatar = null;
    // }
    // if ($user->user_role_id === '3') {
    //     $user->is_child_added = User::where('parent_id', $user->id)->exists() ? 'yes' : 'no';
    //     $user->subscription = Subscription::where('user_id', Auth::id())->latest()->first();
    // } else {
    //     $user_id = $user->parent_id;
    //     $user->subscription = Subscription::where('user_id', $user_id)->latest()->first();
    // }

    //     // $user->image = $user->image ? asset($user->image) : null;
    //     // $user->avtar_image = $childAvatar->image ? asset($childAvatar->image) : null;
    //     // Actual balance
    //     // ✅ Battery balance calculation

    //     $batteryBalance = BatteryEvent::where('user_id', Auth::id())
    //         ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN points ELSE -points END) as balance")
    //         ->value('balance');

    //     $batteryBalance = max(0, $batteryBalance);
    //     $maxCapacity = $user->battery_points;

    //     $percentage = $maxCapacity > 0 ? round(($batteryBalance / $maxCapacity) * 100) : 0;

    //     $user->battery = $batteryBalance;
    //     $user->battery_percentage = min(100, (int) $percentage);

    //     return response()->json([
    //         'status' => true,
    //         'message' => $language == 'english' ? "User Profile Fetched Successfully" : '已成功获取用户个人资料',
    //         'data' => $user
    //     ], 200);
    // }
    public function getProfile(Request $request)
    {
        $user = User::where('id', Auth::id())
            ->with('userAvatar')
            ->first();

        $language = $user->language ?? 'english';

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => $language === 'english' ? 'User not found' : '未找到用户',
                'data' => (object)[]
            ], 404);
        }

        if (!empty($user->dob)) {
            try {
                $dob = Carbon::createFromFormat('Y-m-d', $user->dob . '-01');

                if ($dob->age >= 18) {
                    return response()->json([
                        'status' => false,
                        'message' => $language === 'english'
                            ? 'Account deactivated due to age limit.'
                            : '账户因年龄限制已停用。',
                        'data' => (object)[]
                    ], 200);
                }
            } catch (\Exception $e) {
                \Log::error('DOB Parse Error in getProfile: ' . $e->getMessage());
            }
        }

        if ($user->user_role_id == 5 || $user->user_type == 'teacher') {
            if (!empty($user->school_id)) {
                $schoolStatus = \DB::table('schools')->where('id', $user->school_id)->value('status');

                if ($schoolStatus === 'inactive') {
                    return response()->json([
                        'status' => false,
                        'message' => $language === 'english'
                            ? 'Your school account has been deactivated. Please contact your administration.'
                            : '您的学校账户已停用。请联系学校管理员。',
                        'data' => (object)[]
                    ], 200);
                }
            }
        }

        $todayHits = ApiHitLog::where('user_id', Auth::id())
            ->where('api_name', 'getProfile')
            ->whereDate('created_at', today())
            ->count();

        if ($todayHits < 2) {
            ApiHitLog::create([
                'user_id' => Auth::id(),
                'api_name' => 'getProfile',
            ]);
        }

        $user->age = $user->dob
            ? now()->year - date('Y', strtotime($user->dob))
            : null;

        $childAvatar = $user->userAvatar;

        if ($childAvatar) {
            $avatarParts = AvatarImage::whereIn('id', array_filter([
                $childAvatar->expressions_id,
                $childAvatar->glasses_id,
                $childAvatar->backgrounds_id,
                $childAvatar->shoes_id,
                $childAvatar->hats_id,
                $childAvatar->scarves_id,
                $childAvatar->yoga_mat_id,
                $childAvatar->bottles_id
            ]))
                ->get(['id', 'apply_image', 'preview_image', 'points'])
                ->keyBy('id');

            $user->avatar = [
                'id' => $childAvatar->id,
                'Expressions' => formatAvatarPart($childAvatar->expressions_id, $avatarParts),
                'Glasses' => formatAvatarPart($childAvatar->glasses_id, $avatarParts),
                'Backgrounds' => formatAvatarPart($childAvatar->backgrounds_id, $avatarParts),
                'Shoes' => formatAvatarPart($childAvatar->shoes_id, $avatarParts),
                'Caps' => formatAvatarPart($childAvatar->hats_id, $avatarParts),
                'Scarves' => formatAvatarPart($childAvatar->scarves_id, $avatarParts),
                'YogaMat' => formatAvatarPart($childAvatar->yoga_mat_id, $avatarParts),
                'Bottles' => formatAvatarPart($childAvatar->bottles_id, $avatarParts),
                // 'image' => $childAvatar->image ? asset($childAvatar->image) : null,
                'image' => $childAvatar->image ? getImagePathUrl($childAvatar->image, 'assets/avtar') : null,
                'created_at' => $childAvatar->created_at,
                'updated_at' => $childAvatar->updated_at
            ];
            $childAvatar->image = $childAvatar->image
                ? getImagePathUrl($childAvatar->image, 'assets/avtar')
                : null;
            $user->setRelation('userAvatar', $childAvatar);

            $user->image = $user->image ? getImagePathUrl($user->image, 'assets/avtar') : null;
            $user->avtar_image = $user->avtar_image ? getImagePathUrl($user->avtar_image, 'assets/avtar') : null;
        } else {
            $user->avatar = null;
            $user->image = $user->image ? getImagePathUrl($user->image, 'assets/avtar') : null;
        }
        // if ($user->user_role_id === '3') {
        //     $user->is_child_added = User::where('parent_id', $user->id)->exists() ? 'yes' : 'no';
        //     $user->subscription = Subscription::where('user_id', Auth::id())->latest()->first();
        // }
        // else {
        //     $user_id = $user->parent_id;
        //     $user->subscription = Subscription::where('user_id', $user_id)->latest()->first();
        // }

        if ($user->user_role_id === '3') {
            $user->is_child_added = User::where('parent_id', $user->id)->exists() ? 'yes' : 'no';

            $user->subscription = Subscription::where('user_id', $user->id)
                ->whereIn('status', ['successful', 'cancelled'])
                ->whereDate('end_date', '>=', now())
                ->orderBy('end_date', 'desc')
                ->first();
        } else {
            $parentId = $user->parent_id;
            $user->subscription = Subscription::where('user_id', $parentId)
                ->whereIn('status', ['successful', 'cancelled'])
                ->whereDate('end_date', '>=', now())
                ->orderBy('end_date', 'desc')
                ->first();
        }

        $batteryBalance = BatteryEvent::where('user_id', $user->id)
            ->selectRaw("SUM(CASE WHEN direction = 'credit' THEN points ELSE -points END) as balance")
            ->value('balance');

        $batteryBalance = max(0, (int)$batteryBalance);
        $maxCapacity = (int)$user->battery_points;

        $batteryPercentage = $maxCapacity > 0
            ? round(($batteryBalance / $maxCapacity) * 100)
            : 0;

        $user->battery = $batteryBalance;
        $user->battery_percentage = min(100, $batteryPercentage);
        $school = School::where('id', $user->school_id)->first();
        if ($school) {
            $user->school_code = $school->school_code;
        } else {
            $user->school_code = null;
        }

        return response()->json([
            'status' => true,
            'message' => $language === 'english'
                ? 'User Profile Fetched Successfully'
                : '已成功获取用户个人资料',
            'data' => $user
        ], 200);
    }


    public function deleteChild(Request $request)
    {
        $child = Child::find($request->id);
        $child = User::where('id', $request->id)->first();
        $language = $child->language;
        if ($child) {

            $child->delete();

            return [
                'status' => true,
                'message' => $language == 'english' ? 'Your child account has been deleted successfully.' : '您的帐户已成功删除。',
            ];
        } else {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'Your Child not found.' : '孩子没找到',
            ];
        }
    }
    public function primaryChild(Request $request)
    {
        $user = User::where('id', Auth::id())->first();
        $language = $request->language;
        $child = Child::where('id', $request->id)->first();
        if ($child) {
            $children = Child::whereNot('id', $child->id)->where('parent_id', $request->parent_id)->pluck('id');
            if (count($children) > 0) {
                foreach ($children as $ch) {
                    Child::where('id', $ch)->update(['is_primary' => 'no']);
                }
            }
            $child->update(['is_primary' => 'yes']);
            return [
                'status' => true,
                'message' => $language == 'english' ? 'Data updated successfully.' : '数据更新成功。',
                'data' => $child
            ];
        } else {
            return [
                'status' => false,
                'message' => $language == 'english' ? 'Data not found.' : '数据更新成功。',
                'data' => null
            ];
        }
    }


    // public function parentDashboard(Request $request)
    // {
    //     // $category_id = $request->category_id;
    //     $language = $request->language;
    //     $parent_id = auth()->user()->id;

    //     $users = User::where('parent_id', $parent_id)->get();
    //     $categories = Category::where('status', 'active')->get();

    //     $user_watch_data = [];

    //     foreach ($users as $user) {
    //         $child_id = $user->id;
    //         $user_data = [
    //             'user_id' => $child_id,
    //             'user_name' => $user->name,
    //             'category_watch_data' => []
    //         ];

    //         foreach ($categories as $category) {
    //             $category_id = $category->id;
    //             // $category_name = $category->category_name;
    //             $category_name = $language === 'chinese' ?  ($category->category_name_chinese == null ? $category->category_name : $category->category_name_chinese) : $category->category_name;

    //             $video_contents = VideoContent::where('category_id', $category_id)->where('user_type', 'child')->get();
    //             $video_content_ids = $video_contents->pluck('id')->toArray();
    //             $video_durations = $video_contents->pluck('video_duration')->toArray();

    //             $total_time = 0;
    //             foreach ($video_durations as $time) {
    //                 $parts = explode(':', $time);
    //                 if (count($parts) == 2) { // MM:SS format
    //                     $minutes = (int) $parts[0];
    //                     $seconds = (int) $parts[1];
    //                     $total_time += ($minutes * 60) + $seconds;
    //                 } elseif (count($parts) == 3) { // HH:MM:SS format
    //                     $hours = (int) $parts[0];
    //                     $minutes = (int) $parts[1];
    //                     $seconds = (int) $parts[2];
    //                     $total_time += ($hours * 3600) + ($minutes * 60) + $seconds;
    //                 }
    //             }

    //             $formatted_total_time = gmdate("H:i:s", $total_time);

    //             $last_watched_duration = UserContentWatchHistory::where('child_id', $child_id)
    //                 ->whereIn('video_content_id', $video_content_ids)
    //                 ->pluck('last_watched_duration')
    //                 ->toArray();

    //             $total_last_watched_duration_time = 0;
    //             foreach ($last_watched_duration as $last_watched) {
    //                 $parts = explode(':', $last_watched);
    //                 if (count($parts) == 2) { // MM:SS format
    //                     $minutes = (int) $parts[0];
    //                     $seconds = (int) $parts[1];
    //                     $total_last_watched_duration_time += ($minutes * 60) + $seconds;
    //                 } elseif (count($parts) == 3) { // HH:MM:SS format
    //                     $hours = (int) $parts[0];
    //                     $minutes = (int) $parts[1];
    //                     $seconds = (int) $parts[2];
    //                     $total_last_watched_duration_time += ($hours * 3600) + ($minutes * 60) + $seconds;
    //                 }
    //             }

    //             $watched_percentage = ($total_time > 0) ? round(($total_last_watched_duration_time / $total_time) * 100, 2) : 0;

    //             $user_data['category_watch_data'][] = [
    //                 'category' => $category_name,
    //                 'total_video_duration' => $formatted_total_time,
    //                 'watched_percentage' => (int) $watched_percentage
    //             ];
    //         }

    //         $user_watch_data[] = $user_data;
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => $language == 'chinese' ? '视频内容获取成功！' : 'Data fetched successfully!',
    //         'data' => $user_watch_data,
    //     ], 200);
    // }



    public function parentDashboard(Request $request)
    {
        $language = $request->language;
        $parentId = auth()->user()->id;

        $users = User::where('parent_id', $parentId)->orderBy('created_at', 'desc')->get();
        $categories = Category::all();
        $dashboardData = [];

        foreach ($users as $user) {
            $childId = $user->id;

            $childData = Child::where('parent_id', $parentId)
                ->where('id', $childId)
                ->first();

            $isAccountActive = false;

            if ($childData && !empty($childData->dob)) {
                $dob = Carbon::createFromFormat('Y-m', $childData->dob);
                $isAccountActive = $dob->age >= 18;
            }

            $categoryProgress = [];

            foreach ($categories as $category) {
                $localizedName = getLocalizedCategoryName($category, $language);

                $videoContents = VideoContent::where('category_id', $category->id)
                    ->whereIn('user_type', ['child', 'both']) // ✅ fix here
                    ->get();

                $totalSeconds = $videoContents->sum(function ($video) {
                    return convertTimeToSeconds($video->video_duration);
                });

                $videoIds = $videoContents->pluck('id')->toArray();

                $watchedDurations = UserContentWatchHistory::where('child_id', $childId)
                    ->whereIn('video_content_id', $videoIds)
                    ->pluck('last_watched_duration')
                    ->toArray();

                $watchedSeconds = array_sum(array_map('convertTimeToSeconds', $watchedDurations));

                // $watchedPercentage = $totalSeconds > 0
                //     ? round(($watchedSeconds / $totalSeconds) * 100, 2)
                //     : 0;

                $watchedPercentage = $totalSeconds > 0
                    ? min(100, round(($watchedSeconds / $totalSeconds) * 100, 2))
                    : 0;

                $categoryProgress[] = [
                    'category' => $localizedName,
                    'watched_percentage' => $watchedPercentage,
                    'total_video_duration' => gmdate("H:i:s", $totalSeconds),
                ];
            }
            $topEmotions = $this->getTopEmotions($childId, 3, 'english');

            $dashboardData[] = [
                'user_id' => $childId,
                'user_name' => $user->name,
                'category_watch_data' => $categoryProgress,
                'top_mood_emotion' => $topEmotions,
                'is_account_active' => $isAccountActive,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => $language === 'chinese' ? '视频内容获取成功！' : 'Data fetched successfully!',
            'data' => $dashboardData,
        ], 200);
    }


    public function getTopEmotions(int $childId, int $limit = 3, string $language = 'english'): Collection
    {

        // $childId =  auth()->user()->id;
        // $topEmotions = $this->getTopEmotions($childId, 3, $language);
        // Use current month and year
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;

        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $entries = ChildMood::where('child_id', $childId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->get();

        $moodFrequency = [];

        foreach ($entries as $entry) {
            $mood = Mood::find($entry->mood_id);
            if (!$mood) continue;

            $moodName = $language === 'chinese' ? ($mood->name_chinese ?? $mood->name) : $mood->name;

            if (isset($moodFrequency[$moodName])) {
                $moodFrequency[$moodName]['count'] += 1;
            } else {
                $moodFrequency[$moodName] = [
                    'count' => 1,
                    'image' => $mood->image,
                    'color' => $mood->color ?? '#CCCCCC',
                ];
            }
        }

        return collect($moodFrequency)
            ->sortByDesc('count')
            ->take($limit)
            ->map(function ($item, $moodName) {
                return [
                    'mood_name' => $moodName,
                    'count' => $item['count'],
                    'image' => $item['image'],
                    'color' => $item['color'],
                ];
            })->values();
    }


    function convertTimeToSecondsNew($time)
    {
        if (!$time) return 0;
        sscanf($time, "%d:%d:%d", $h, $m, $s);
        return ($h * 3600) + ($m * 60) + $s;
    }

    public function updateBatteryAndLoyalty(Request $request)
    {
        $childId = auth()->user()->id;

        $watchHistories = UserContentWatchHistory::where('child_id', $childId)
            ->where('is_completed', 'no')
            ->get();

        $totalEarnedPoints = 0;
        $batterySum = 0;
        $batteryCount = 0;

        foreach ($watchHistories as $watch) {
            $video = VideoContent::find($watch->video_content_id); // Get video data

            if (!$video || !$video->point) continue; // Skip if no video or point not defined

            $videoPoint = $video->point;

            $videoDurationSeconds = $this->convertTimeToSecondsNew($watch->total_video_duration);
            $watchedDurationSeconds = $this->convertTimeToSecondsNew($watch->last_watched_duration);

            if ($videoDurationSeconds <= 0) continue;

            $watchPercent = $watchedDurationSeconds / $videoDurationSeconds;

            $earnedPoint = round($watchPercent * $videoPoint, 2);
            $batteryPercentage = round($watchPercent * 100, 2);

            $totalEarnedPoints += $earnedPoint;
            $batterySum += $batteryPercentage;
            $batteryCount++;

            // Mark as completed to prevent reprocessing
            $watch->is_completed = 'yes';
            $watch->save();
        }

        $averageBattery = $batteryCount > 0 ? round($batterySum / $batteryCount, 2) : 0;

        // Update child table
        $child = Child::find($childId);
        if ($child) {
            $child->battery_percentage = $averageBattery;
            $child->loyalty_points += $totalEarnedPoints;
            $child->save();
        }

        // Update user table
        $user = User::find($childId);
        if ($user) {
            $user->loyalty_points += $totalEarnedPoints;
            $user->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Battery and loyalty updated successfully.',
            'battery_percentage' => $averageBattery,
            'earned_points' => $totalEarnedPoints
        ]);
    }









    // public function subscription(Request $request)
    // {
    //     $user = auth()->user();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not authenticated.',
    //         ], 401);
    //     }

    //     Subscription::create([
    //         'user_id' => $user->id,
    //         'subscription_type_id' => $request->subscription_type_id,
    //         'user_type' => $request->user_type,
    //         'subscription_type' => $request->subscription_type,
    //         'start_date' => $request->start_date,
    //         'end_date' => $request->end_date,
    //         'currency' => $request->currency,
    //         'status' => $request->status ?? 'Successful',
    //         'price' => $request->price
    //     ]);

    //     $emailData = [
    //         'email' => $user->email,
    //         'name' => $user->name,
    //         'subscription_type' => $request->subscription_type,
    //         'user_type' => $request->user_type,
    //         'start_date' => $request->start_date,
    //         'end_date' => $request->end_date,
    //         'currency' => $request->currency,
    //         'price' => $request->price,
    //         'status' => $request->status ?? 'Successful',
    //     ];

    //     ___mail_sender($emailData['email'], 'subscription', $emailData, 'english');

    //     return response()->json([
    //         'status' => true,
    //         'message' => $request->language == 'chinese' ? '视频内容获取成功！' : 'Subscription purchased successfully!',
    //     ], 200);
    // }
    public function subscription(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        // -----------------------------
        // STEP 1: Validate payload
        // -----------------------------
        $request->validate([
            'platform'             => 'required|in:android,ios',
            'subscription_type_id' => 'required',
            'subscription_type'    => 'required',
            'user_type'            => 'required',
            'transactionReceipt'   => 'required',
            'currency'             => 'required',
            'price'                => 'required',
            'start_date'           => 'required',
            'end_date'             => 'required',
        ]);

        // -----------------------------

        // STEP 2: Validate receipt (ANDROID / IOS)
        // -----------------------------
        // if ($request->platform === 'android') {

        //     $receiptResult = $this->validateGooglePlayReceipt(
        //         $request->transactionReceipt,
        //         $request->subscription_type_id
        //     );
        // } else {
        //     $receiptResult = $this->validateAppleReceipt(
        //         $request->transactionReceipt
        //     );
        // }

        if ($request->platform === 'android') {
            // TEMP: Direct success for Android (bypass verification)
            $receiptResult = [
                'status' => true,
                'transaction_id' => $request->transactionReceipt, // ya uniqid('android_txn_')
            ];
        } else {
            $receiptResult = $this->validateAppleReceipt(
                $request->transactionReceipt
            );
        }


        if (!$receiptResult['status']) {
            return response()->json([
                'status' => false,
                'message' => $request->language === 'chinese' ? '支付验证失败' : 'Payment verification failed',
            ], 200);
        }

        // -----------------------------
        // STEP 3: Prevent duplicate purchase
        // -----------------------------
        $existingSubscription = Subscription::where('transaction_id', $receiptResult['transaction_id'])->where('user_id', $user->id)->first();

        // -----------------------------
        // STEP 4: Create subscription
        // -----------------------------
        if ($existingSubscription) {
            if (Carbon::parse($existingSubscription->end_date)->gte(now())) {
                return response()->json([
                    'status'  => false,
                    'message' => $request->language === 'chinese'
                        ? '订阅已存在'
                        : 'Subscription already exists',
                ], 200);
            }

            $existingSubscription->update([
                'subscription_type_id' => $request->subscription_type_id,
                'subscription_type'    => $request->subscription_type,
                'user_type'            => $request->user_type,
                'start_date'           => $request->start_date,
                'end_date'             => $request->end_date,
                'currency'             => $request->currency,
                'price'                => $request->price,
                'status'               => 'Successful',
                'receipt'              => $request->transactionReceipt,
            ]);
        } else {

            Subscription::create([
                'user_id'              => $user->id,
                'subscription_type_id' => $request->subscription_type_id,
                'subscription_type'    => $request->subscription_type,
                'user_type'            => $request->user_type,
                'start_date'           => $request->start_date,
                'end_date'             => $request->end_date,
                'currency'             => $request->currency,
                'price'                => $request->price,
                'status'               => 'Successful',
                'transaction_id'       => $receiptResult['transaction_id'],
                'receipt'              => $request->transactionReceipt,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => $request->language == 'chinese' ? '视频内容获取成功！' : 'Subscription purchased successfully!',
        ], 200);
    }

    private function validateGooglePlayReceipt($receipt, $productId)
    {
        // if (app()->environment('local')) {
        //     return [
        //         'status' => true,
        //         'transaction_id' => 'local_test_txn_fixed_001',
        //     ];
        // }

        $packageName = env('GOOGLE_PACKAGE_NAME');
        $accessToken = $this->getGoogleAccessToken();

        if (!$accessToken) {
            return ['status' => false];
        }

        $url = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/subscriptions/{$productId}/tokens/{$receipt}";

        $response = Http::withToken($accessToken)->get($url);

        if ($response->failed()) {
            return ['status' => false];
        }

        $data = $response->json();

        if (($data['purchaseState'] ?? 1) != 0) {
            return ['status' => false];
        }
        // 🔴 subscription expired check
        if (
            isset($data['expiryTimeMillis']) &&
            $data['expiryTimeMillis'] < (time() * 1000)
        ) {
            return ['status' => false];
        }


        return [
            'status' => true,
            'transaction_id' => $data['orderId'] ?? uniqid('txn_'),
        ];
    }
    private function validateAppleReceipt($receipt)
    {
        $payload = [
            'receipt-data' => $receipt,
            'password' => env('APPLE_SHARED_SECRET'),
            'exclude-old-transactions' => true,
        ];

        // Production
        $response = Http::post(
            'https://buy.itunes.apple.com/verifyReceipt',
            $payload
        );

        $data = $response->json();

        // Sandbox receipt sent to production
        if (($data['status'] ?? null) === 21007) {
            $response = Http::post(
                'https://sandbox.itunes.apple.com/verifyReceipt',
                $payload
            );
            $data = $response->json();
        }

        if (($data['status'] ?? 1) !== 0) {
            return ['status' => false];
        }

        $latest = $data['latest_receipt_info'][0] ?? null;

        if (!$latest) {
            return ['status' => false];
        }


        return [
            'status' => true,
            'transaction_id' => $latest['transaction_id'] ?? uniqid('ios_txn_'),
        ];
    }
    private function getGoogleAccessToken()
    {
        $jsonPath = base_path(env('GOOGLE_SERVICE_ACCOUNT'));

        if (!file_exists($jsonPath)) {
            return null;
        }

        $jsonKey = json_decode(file_get_contents($jsonPath), true);

        $jwtHeader = rtrim(strtr(base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ])), '+/', '-_'), '=');

        $now = time();

        $jwtClaim = rtrim(strtr(base64_encode(json_encode([
            'iss'   => $jsonKey['client_email'],
            'scope' => 'https://www.googleapis.com/auth/androidpublisher',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ])), '+/', '-_'), '=');

        openssl_sign(
            "$jwtHeader.$jwtClaim",
            $signature,
            $jsonKey['private_key'],
            'SHA256'
        );

        $jwt = "$jwtHeader.$jwtClaim." . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');


        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        return $response->json()['access_token'] ?? null;
    }
}
