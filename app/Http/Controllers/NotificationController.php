<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Models\PermissionUser;
use App\Models\Mood;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Activity;
use Auth;
use Carbon\Carbon;
use Validator;
use App\Models\ContactUs;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\App;

class NotificationController extends Controller
{
    public function notificationList(Request $request)
    {
        $user = auth()->user();

        $getNotificationList = AppNotification::where('user_id', $user->id)
            ->latest() // orders by created_at DESC
            ->get()
            ->toArray();

        if (count($getNotificationList) > 0) {
            return [
                "status" => true,
                "message" => "Notifications retrieved successfully.",
                'data' => $getNotificationList,
            ];
        } else {
            return [
                "status" => true,
                "message" => "No notifications found.",
                'data' => [],
            ];
        }
    }


    public function sendMessage(Request $request)
    {
        $lan = $request->language ?? 'english';

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error($validator->errors()->first(), 422);
        }

        $user = auth()->user();

        // The confirmation used to go to a caller-supplied address, which made
        // this an open relay. It goes to the authenticated user instead.
        $emailData = [
            'name' => $request->name,
            'email' => $user->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];

        $admin = User::where('user_type', 'admin')->first();

        ContactUs::create($emailData);

        if ($admin && $admin->email) {
            sendContactEmail($admin->email, 'contact_support', $emailData, $lan);
        } else {
            \Log::error('contact-support: no admin user with an email address configured.');
        }

        if ($user->email) {
            ___mail_sender($user->email, 'contact_support_user', $emailData, $lan);
        }
        return ApiResponse::success($emailData, $lan == "english" ? "Your message has been sent successfully!" : "您的消息已成功发送！", 200);
    }

    public function markAsRead(Request $request)
    {
        $language = $request->language ?? 'english';
        AppNotification::where('id', $request->notification_id)
            ->where('user_id', auth()->id())
            ->update([
                'status' => 'seen'
            ]);
        return ApiResponse::success(
            null,
            $language == 'english' ? "Status marked as read" : '状态标记为已读'
        );
    }

    public function deleteNotification(Request $request)
    {
        $language = $request->language ?? 'english';
        AppNotification::where('id', $request->notification_id)
            ->where('user_id', auth()->id())
            ->delete();
        return ApiResponse::success(
            null,
            $language == 'english' ? "Notification Deleted Successfully" : '通知已成功删除'
        );
    }

    public function manageNotification(Request $request)
    {
        $request->validate([
            'notification' => 'required',
        ]);

        $user = auth()->user();

        // Convert "true"/"false" string to boolean
        $notification = filter_var($request->notification, FILTER_VALIDATE_BOOLEAN);

        $user->update([
            'is_notification' => $request->notification,
        ]);

        $msg = $notification ? 'ON' : 'OFF';

        return ApiResponse::success(
            ['is_notification' => $msg],
            "Notification turned {$msg} successfully!",
            200,
            ['is_notification' => $msg]
        );
    }
}
