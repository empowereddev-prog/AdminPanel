<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AvatarImage;
use App\Models\BatteryEvent;
use App\Models\UserAvtarImage;
use App\Models\Category;
use App\Models\Child;
use App\Models\User;
use App\Models\UserUnlockAvtar;
use Yajra\Datatables\datatables;
use Validator;
use App\Models\VideoContent;
use App\Models\UserContentWatchHistory;
use Illuminate\Support\Facades\DB;

class AvtarController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesApiUser;

    // public function avtarImage(Request $request){
    //     $avatarParts = AvatarImage::where('status','active')->get()->toArray();
    //     $formattedData = [];

    //     foreach ($avatarParts as $part) {
    //         if (!isset($formattedData[$part['body_part']])) {
    //             $formattedData[$part['body_part']] = [];
    //         }

    //     $childAvatar = UserUnlockImage::where('child_id',$request->id)->get();
    //     // $matchingIds = [
    //     //             $childAvatar->backgrounds_id ?? '',
    //     //             $childAvatar->expressions_id ?? '',
    //     //             $childAvatar->glasses_id ?? '',
    //     //             $childAvatar->shoes_id ?? '',
    //     //             $childAvatar->hats_id ?? '',
    //     //             $childAvatar->scarves_id ?? '',
    //     // ];
    //     // $isAvailable = in_array($part['id'], $matchingIds) ? 'yes' : 'no';
    //             $formattedData[$part['body_part']][] = [
    //                 'id' => $part['id'],
    //                 'preview_image' => asset('assets/avtar/' . $part['preview_image']),
    //                 'apply_image' => asset('assets/avtar/' . $part['apply_image']),
    //                 'points' => $part['points'], // If points are dynamic, update accordingly
    //                 'status' => "active", // If status is stored in DB, update accordingly
    //                 'created_at' => $part['created_at'],
    //                 'updated_at' => $part['updated_at'],
    //                 // 'is_available' => $isAvailable,
    //             ];
    //     }
    //         return response()->json([
    //         'status' => true,
    //         'message' => 'Data fetched successfully!',
    //         'data' => [$formattedData]
    //     ], 200);
    // }


    public function avtarImage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $targetId = $this->resolveTargetUserId($request, 'id');

        if (!$targetId) {
            return $this->unauthorisedTargetResponse($request->language ?? 'english');
        }

        $avatarParts = AvatarImage::where('status', 'active')->orderBy('created_at', 'asc')->get();
        $childAvatars = UserUnlockAvtar::where('child_id', $targetId)->pluck('type_id')->toArray();

        $formattedData = [];

        foreach ($avatarParts as $part) {
            if (!isset($formattedData[$part->body_part])) {
                $formattedData[$part->body_part] = [];
            }

            $isAvailable = in_array($part->id, $childAvatars) ? 'yes' : 'no';

            $formattedData[$part->body_part][] = [
                'id' => $part->id,
                // 'preview_image' => asset('assets/avtar/' . $part->preview_image),
                // 'apply_image' => asset('assets/avtar/' . $part->apply_image),
                'preview_image' => $part->preview_image ? getImagePathUrl($part->preview_image, 'assets/avtar') : null,
                'apply_image'   => $part->apply_image   ? getImagePathUrl($part->apply_image, 'assets/avtar')   : null,

                'points' => $part->points,
                'status' => "active",
                'created_at' => $part->created_at,
                'updated_at' => $part->updated_at,
                'is_available' => $isAvailable,
            ];
        }

        return ApiResponse::success($formattedData, 'Data fetched successfully!', 200);
    }


    public function storeAvtar(Request $request)
    {
        $language = $request->language;
        // Validate the request
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|exists:users,id',
            'expressions_id' => 'nullable|string',
            'glasses_id' => 'nullable|string',
            'backgrounds_id' => 'nullable|string',
            'shoes_id' => 'nullable|string',
            'caps_id' => 'nullable|string',
            'scarves_id' => 'nullable|string',
            'yoga_mat_id' => 'nullable|string',
            'bottles_id' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg' // Ensure it's an image and max 2MB
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation Error!', 422, $validator->errors()->first());
        }

        $childId = $this->resolveTargetUserId($request, 'child_id');

        if (!$childId) {
            return $this->unauthorisedTargetResponse($language ?? 'english');
        }

        $childAvatar = UserAvtarImage::where('child_id', $childId)->first();

        // $imageName was previously only set inside the hasFile() branch, so a
        // save without a file stored null and wiped users.avtar_image.
        $imageName = $childAvatar->image ?? null;


        // Handle image upload
        // $imagePath = null;
        // $imageName = null;
        // if ($request->hasFile('image')) {
        //     $file = $request->file('image');
        //     $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        //     $imagePath = asset('assets/avtar/' . $imageName);
        //     $file->move(public_path('assets/avtar'), $imageName);
        // }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Delete old image from S3 if exists
            $oldImage = $childAvatar->image ?? null;
            // Upload new image to S3 (helper function handles unique name)
            $imageName = uploadFile($file, 'assets/avtar', $oldImage);
        }


        // delete-then-create: without a transaction a failure on the create left
        // the child with no avatar row at all.
        $childAvatar = DB::transaction(function () use ($childAvatar, $childId, $request, $imageName) {
            if ($childAvatar) {
                $childAvatar->delete();
                $childAvatar = UserAvtarImage::create([
                    'child_id' => $childId,
                    'expressions_id' => $request->expressions_id ?? null,
                    'glasses_id' => $request->glasses_id ?? null,
                    'backgrounds_id' => $request->backgrounds_id ?? null,
                    'shoes_id' => $request->shoes_id ?? null,
                    'hats_id' => $request->caps_id ?? null,
                    'scarves_id' => $request->scarves_id ?? null,
                    'yoga_mat_id' => $request->yoga_mat_id ?? null,
                    'bottles_id' => $request->bottles_id ?? null,
                    'image' => $imageName // Save only the filename
                ]);
            } else {
                // Store data in the database
                $childAvatar = UserAvtarImage::create([
                    'child_id' => $childId,
                    'expressions_id' => $request->expressions_id ?? null,
                    'glasses_id' => $request->glasses_id ?? null,
                    'backgrounds_id' => $request->backgrounds_id ?? null,
                    'shoes_id' => $request->shoes_id ?? null,
                    'hats_id' => $request->caps_id ?? null,
                    'scarves_id' => $request->scarves_id ?? null,
                    'yoga_mat_id' => $request->yoga_mat_id ?? null,
                    'bottles_id' => $request->bottles_id ?? null,
                    'image' => $imageName // Save only the filename
                ]);
            }

            // Update child's image field
            User::where('id', $childId)->update(['avtar_image' => $imageName]);

            return $childAvatar;
        });

        // ✅ Battery Debit Logic
        // $usedAvatarParts = [
        //     $request->expressions_id,
        //     $request->glasses_id,
        //     $request->backgrounds_id,
        //     $request->shoes_id,
        //     $request->caps_id,
        //     $request->scarves_id,
        //     $request->yoga_mat_id,
        //     $request->bottles_id
        // ];

        // if (collect($usedAvatarParts)->filter()->isNotEmpty()) {
        //     $user = User::find($request->child_id);
        //     // if (!$user || $user->battery_points < 10) {
        //     //     return response()->json([
        //     //         'status' => false,
        //     //         'message' => 'Not enough battery points to update avatar. Please recharge or earn more battery points.',
        //     //     ], 200);
        //     // }

        //     if ($user && $user->battery_points >= 10) {
        //         // 🔹 Debug dekhne ke liye
        //         // dd($user->battery_points);

        //         // 🔹 Battery Event log
        //         BatteryEvent::create([
        //             'user_id' => $user->id,
        //             'direction' => 'debit',
        //             'points' => 10,
        //             'reason' => 'Avatar part used',
        //             'effective_date' => now(),
        //         ]);

        //         // 🔹 User table me 10 points kam karo
        //         $user->decrement('battery_points', 10);
        //     }
        // }

        return ApiResponse::success($childAvatar, $language === 'chinese' ? '头像保存成功！' : 'Avatar saved successfully!', 200);
    }


    // public function storeAvtar(Request $request)
    // {
    //     // Determine validation messages based on language
    //     $language = $request->language ?? 'english';
    //     $messages = [
    //         'required' => $language === 'chinese' ? '此字段是必填项。' : 'This field is required.',
    //         'exists' => $language === 'chinese' ? '所选项不存在。' : 'The selected item does not exist.',
    //         'string' => $language === 'chinese' ? '此字段必须是字符串。' : 'This field must be a string.',
    //         'image' => $language === 'chinese' ? '文件必须是图片（jpeg, png, jpg）。' : 'The file must be an image (jpeg, png, jpg).',
    //     ];

    //     // Validate the request
    //     $validator = Validator::make($request->all(), [
    //         'child_id' => 'required|exists:children,id',
    //         'type' => 'required|string|in:Expressions,Glasses,Backgrounds,Shoes,Caps,Scarves',
    //         'id' => 'nullable|string',
    //         'image' => 'nullable|image|mimes:jpeg,png,jpg' // Ensure it's an image
    //     ], $messages);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language === 'chinese' ? '验证错误！' : 'Validation Error!',
    //             'errors' => $validator->errors()->first()
    //         ], 422);
    //     }

    //     $childAvatar = UserAvtarImage::where('child_id', $request->child_id)->first();
    //     $imagePath = null;

    //     // Handle image upload
    //     if ($request->hasFile('image')) {
    //         $file = $request->file('image');
    //         $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //         $file->move(public_path('assets/avtar'), $imageName);
    //         $imagePath = asset('assets/avtar/' . $imageName);
    //     }
    //     $type = strtolower($request->type) === 'caps' ? 'hats' : strtolower($request->type);

    //     // Determine which field to update
    //     $updateData = [$type . '_id' => $request->id];
    //     if ($imagePath) {
    //         $updateData['image'] = $imagePath;
    //     }

    //     if ($childAvatar) {
    //         $childAvatar->update($updateData);
    //     } else {
    //         $updateData['child_id'] = $request->child_id;
    //         $childAvatar = UserAvtarImage::create($updateData);
    //     }

    //     // Update child's image field if an image was uploaded
    //     if ($imagePath) {
    //         Child::where('id', $request->child_id)->update(['image' => $imagePath]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => $language === 'chinese' ? '头像保存成功！' : 'Avatar saved successfully!',
    //         'data' => $childAvatar
    //     ], 200);
    // }


    // get video category
    // public function videoCategory(Request $request){
    //     $language = $request->language;
    //     $videoCategory = Category::where('status','active')->get();

    //     $filteredSessions = $videoCategory->map(function ($session) use ($language) {

    //         // Set title and description based on language
    //         $session->category_name = $language === 'chinese' ?  ($session->category_name_chinese == null ? $session->category_name : $session->category_name_chinese) : $session->category_name;

    //         // Remove the unnecessary fields
    //         unset($session->category_name_chinese);

    //         return $session;
    //     });
    //     return response()->json([
    //         'status' => true,
    //         'message' => $language === 'chinese' ? '类别获取成功！' :  'Category fetched successfully!',
    //         'data' => $filteredSessions->values()
    //     ], 200);
    // }

    // public function videoCategory(Request $request)
    // {
    //     $language = $request->language;
    //     $videoCategory = Category::where('status', 'active')->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')->get();

    //     $filteredSessions = $videoCategory->map(function ($session) use ($language) {

    //         // Set title and description based on language
    //         $session->category_name = $language === 'chinese' ?  ($session->category_name_chinese == null ? $session->category_name : $session->category_name_chinese) : $session->category_name;

    //         // Remove the unnecessary fields
    //         unset($session->category_name_chinese);

    //         $video_content_points = VideoContent::where('category_id', $session->id)->whereIn('user_type', ['child', 'both'])->sum('points');

    //         $video_durations = VideoContent::where('category_id', $session->id)->whereIn('user_type', ['child', 'both'])->pluck('video_duration')->toArray();

    //         // Convert video time to total seconds
    //         $total_time = 0;
    //         foreach ($video_durations as $time) {
    //             $parts = explode(':', $time);
    //             if (count($parts) == 2) { // MM:SS format
    //                 $minutes = (int) $parts[0];
    //                 $seconds = (int) $parts[1];
    //                 $total_time += ($minutes * 60) + $seconds;
    //             } elseif (count($parts) == 3) { // HH:MM:SS format
    //                 $hours = (int) $parts[0];
    //                 $minutes = (int) $parts[1];
    //                 $seconds = (int) $parts[2];
    //                 $total_time += ($hours * 3600) + ($minutes * 60) + $seconds;
    //             }
    //         }
    //         $video_content_ids = VideoContent::where('category_id', $session->id)->whereIn('user_type', ['child', 'both'])->pluck('id')->toArray();
    //         $last_watched_duration = UserContentWatchHistory::where('child_id', auth()->user()->id)->whereIn('video_content_id', $video_content_ids)->pluck('last_watched_duration')->toArray();
    //         $total_last_watched_duration_time = 0;
    //         foreach ($last_watched_duration as $last_watched) {
    //             $last_watched_duration_parts = explode(':', $last_watched);
    //             if (count($last_watched_duration_parts) == 2) { // MM:SS format
    //                 $minutes = (int) $last_watched_duration_parts[0];
    //                 $seconds = (int) $last_watched_duration_parts[1];
    //                 $total_last_watched_duration_time += ($minutes * 60) + $seconds;
    //             } elseif (count($last_watched_duration_parts) == 3) { // HH:MM:SS format
    //                 $hours = (int) $parts[0];
    //                 $minutes = (int) $parts[1];
    //                 $seconds = (int) $parts[2];
    //                 $total_last_watched_duration_time += ($hours * 3600) + ($minutes * 60) + $seconds;
    //             }
    //         }
    //         $remaining_seconds = max(0, $total_time - $total_last_watched_duration_time);

    //         $session->watched_percentage = ($total_time > 0) ? round(($total_last_watched_duration_time / $total_time) * 100, 2) : 0;

    //         // $formatted_total_time = gmdate("H:i:s", $total_time);
    //         // $formatted_remaining_time = gmdate("H:i:s", $remaining_seconds);

    //         return $session;
    //     });
    //     return response()->json([
    //         'status' => true,
    //         'message' => $language === 'chinese' ? '类别获取成功！' :  'Category fetched successfully!',
    //         'data' => $filteredSessions->values()
    //     ], 200);
    // }
    public function videoCategory(Request $request)
    {
        $language = $request->language;
        $childId  = auth()->user()->id;

        $videoCategories = Category::where('status', 'active')
            ->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->get();

        $filteredSessions = $videoCategories->map(function ($session) use ($language, $childId) {

            // ✅ Category name (language wise)
            $session->category_name = $language === 'chinese'
                ? ($session->category_name_chinese ?? $session->category_name)
                : $session->category_name;

            unset($session->category_name_chinese);

            // ✅ Get all videos of this category
            $videos = VideoContent::where('category_id', $session->id)
                ->whereIn('user_type', ['child', 'both'])
                ->get();

            if ($videos->isEmpty()) {
                $session->watched_percentage = 0;
                return $session;
            }

            // ✅ Get watch histories (single query)
            $histories = UserContentWatchHistory::where('child_id', $childId)
                ->whereIn('video_content_id', $videos->pluck('id'))
                ->get()
                ->keyBy('video_content_id');

            $totalCategorySeconds  = 0;
            $totalWatchedSeconds   = 0;

            foreach ($videos as $video) {

                // 🔹 Convert video duration to seconds
                $v = explode(':', $video->video_duration);
                $videoSeconds = count($v) == 2
                    ? ($v[0] * 60 + $v[1])
                    : ($v[0] * 3600 + $v[1] * 60 + $v[2]);

                // add to total category duration
                $totalCategorySeconds += $videoSeconds;

                // 🔹 If not watched at all
                if (!isset($histories[$video->id])) {
                    continue;
                }

                $history = $histories[$video->id];

                // 🔹 Completed video → full duration counted
                if ($history->is_completed === 'yes') {
                    $totalWatchedSeconds += $videoSeconds;
                    continue;
                }

                // 🔹 Partial watched
                $w = explode(':', $history->last_watched_duration);
                $watchedSeconds = count($w) == 2
                    ? ($w[0] * 60 + $w[1])
                    : ($w[0] * 3600 + $w[1] * 60 + $w[2]);

                // cap watched time to video duration
                $totalWatchedSeconds += min($watchedSeconds, $videoSeconds);
            }

            // ✅ Final percentage
            $session->watched_percentage = $totalCategorySeconds > 0
                ? round(($totalWatchedSeconds / $totalCategorySeconds) * 100, 2)
                : 0;

            return $session;
        });

        return ApiResponse::success($filteredSessions->values(), $language === 'chinese'
                ? '类别获取成功！'
                : 'Category fetched successfully!', 200);
    }


    // public function userUnlockAvatars(Request $request)
    // {
    //     $language = $request->language;
    //     $child = User::findOrFail($request->child_id);
    //     if ($child->loyalty_points < $request->points) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $language === 'chinese' ? '孩子的忠诚度点数不够。' : "Child's Loyalty points are not enough.",
    //             'data' => null
    //         ], 400);
    //     }
    //     $avtar = UserUnlockAvtar::create([
    //         'child_id' => $request->child_id,
    //         'type' => $request->type,
    //         'type_id' => $request->type_id,
    //         'points' => $request->points,
    //         'is_available' => 'yes'
    //     ]);

    //     $totalUsedPoints = UserUnlockAvtar::where('child_id', $request->child_id)->sum('points');

    //     // Get child record

    //     // Deduct points from child's loyalty_points
    //     $child->loyalty_points = max(0, $child->loyalty_points - $request->points);
    //     $child->save();
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Avtar saved successfully!',
    //         'data' => $avtar
    //     ], 200);
    // }
    public function userUnlockAvatars(Request $request)
    {
        $language = $request->language;

        // 'points' was unvalidated: a negative value passed the balance check
        // below, logged a negative debit and then *credited* the account.
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|exists:users,id',
            'type'     => 'required|string',
            'type_id'  => 'required',
            'points'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 422);
        }

        $childId = $this->resolveTargetUserId($request, 'child_id');

        if (!$childId) {
            return $this->unauthorisedTargetResponse($language ?? 'english');
        }

        $points = (int) $request->points;

        try {
            // Locked so concurrent unlocks cannot both pass the balance check,
            // and so the three writes below cannot land partially.
            $avtar = DB::transaction(function () use ($childId, $request, $points) {
                $child = User::whereKey($childId)->lockForUpdate()->firstOrFail();

                if ($child->battery_points < $points) {
                    return null;
                }

                $avtar = UserUnlockAvtar::create([
                    'child_id'   => $childId,
                    'type'       => $request->type,
                    'type_id'    => $request->type_id,
                    'points'     => $points,
                    'is_available' => 'yes'
                ]);

                BatteryEvent::create([
                    'user_id' => $child->id,
                    'direction' => 'debit',
                    'points' => $points,
                    'reason' => 'Avatar unlocked',
                    'effective_date' => now(),
                ]);

                $child->battery_points = max(0, $child->battery_points - $points);
                $child->save();

                return $avtar;
            });
        } catch (\Throwable $e) {
            \Log::error('userUnlockAvatars failed: ' . $e->getMessage());

            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => $language === 'chinese' ? '解锁头像失败。' : 'Failed to unlock avatar.',
                'data' => null
            ], 200);
        }

        if (!$avtar) {
            // Not migrated: the contract is data => null, which ApiResponse renders
            // as {} - a type change for the shipped app. Convert with a client release.
            // @envelope-exempt
            return response()->json([
                'status' => false,
                'message' => $language === 'chinese'
                    ? '孩子的电池点数不够。'
                    : "Child's Battery are not enough.",
                'data' => null
            ], 200);
        }

        return ApiResponse::success($avtar, $language === 'chinese'
                ? '头像解锁成功！'
                : 'Avatar unlocked successfully!', 200);
    }
}
