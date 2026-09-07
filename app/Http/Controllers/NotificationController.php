<?php

namespace App\Http\Controllers;

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


    public function sendNotification()
    {
        $title = "Test Notification";
        $message = "Hello Test for notification Sahil";
        $notification_type = "Added";
        $data = ["abc"];
        $user_type = "user";
        $users = DeviceToken::whereNotNull('user_id')
            ->whereNotNull('token')
            ->get();
        foreach ($users as $user) {
            sendNotificationSender($user->user_id, $title, $message, $notification_type, $data, $user_type);
        }
        return [
            "status" => true,
            "message" => "Notifications Send Done ",
            'data' => [],
        ];
    }

    public function sendMessage(Request $request)
    {
        $lan = $request->language ?? 'english';
        $emailData = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
        ];
        $admin  = User::where('user_type', 'admin')->first();

        ContactUs::create($emailData);
        sendContactEmail($admin->email, 'contact_support', $emailData, $lan);
        ___mail_sender($request->email, 'contact_support_user', $emailData, $lan);
        return response()->json([
            "status" => true,
            "message" => $lan == "english" ? "Your message has been sent successfully!" : "您的消息已成功发送！",
            "data" => $emailData,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $language = $request->language ?? 'english';
        AppNotification::where('id', $request->notification_id)->update([
            'status' => 'seen'
        ]);
        return response()->json([
            "status" => true,
            "message" => $language == 'english' ? "Status marked as read" : '状态标记为已读',
        ]);
    }

    public function deleteNotification(Request $request)
    {
        $language = $request->language ?? 'english';
        AppNotification::where('id', $request->notification_id)->delete();
        return response()->json([
            "status" => true,
            "message" => $language == 'english' ? "Notification Deleted Successfully" : '通知已成功删除',
        ]);
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

        return response()->json([
            "status" => true,
            "message" => "Notification turned {$msg} successfully!",
            "is_notification" => $msg
        ], 200);
    }
}
