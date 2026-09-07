<?php

use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\StaticContent;
use App\Models\AdminMenu;
use App\Models\PermissionUsers;
use App\Models\Notification;
use App\Models\AppNotification;
use App\Models\BatteryEvent;
use App\Models\DeviceToken;
use App\Models\NotificationTemplate;
use App\Models\PermissionUser;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Twilio\Rest\Client;
use App\Notifications\CustomNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;

if (!function_exists('___mail_sender')) {
    function ___mail_sender($email, $template_code, $data, $lan)
    {
        // dd($email);
        $template = EmailTemplate::where('variable_name', $template_code)->where('language', $lan)->first();
        if (!empty($template)) {
            $variables = explode(',', $template->variables);
            // dd($variables,$data);
            $subject = $template->subject;
            $body = $template->description;
            foreach ($variables as $item) {
                $subject = str_replace($item, $data[str_replace(array('{', '}'), '', $item)], stripslashes(html_entity_decode($subject)));
                $body = str_replace($item, $data[str_replace(array('{', '}'), '', $item)], stripslashes(html_entity_decode($body)));
            }
            // dd($variables);
            Config::set(['port' => env("MAIL_PORT"), 'host' => env("MAIL_HOST"), 'username' => env("MAIL_USERNAME"), 'password' => env("MAIL_PASSWORD")]);

            $sender = ['subject' => $subject, 'email' => $email, 'from' => ['address' => 'dummy@gmail.com', 'name' => 'Empower Ed']];
            if (!empty($body) && !empty($email)) {
                Mail::send('emails.default', ['body' => $body], function ($message) use ($sender, $data) {
                    $message->to(
                        $sender['email']
                    )
                        ->subject($sender['subject'])
                        ->from(
                            $sender['from']['address'],
                            $sender['from']['name']
                        );
                    // Check if $data['pdf'] exists before attaching it
                    if (isset($data['pdf'])) {
                        $message->attachData($data['pdf']->output(), "invoice.pdf");
                    }
                });
            }
        }
    }
}
function sendContactEmail($userEmail, $template_code, $data, $lan)
{
    $template = EmailTemplate::where('variable_name', $template_code)->where('language', $lan)->first();
    if (!empty($template)) {
        $variables = explode(',', $template->variables);
        // dd($variables,$data);
        $subject = $template->subject;
        $body = $template->description;
        foreach ($variables as $item) {
            $subject = str_replace($item, $data[str_replace(array('{', '}'), '', $item)], stripslashes(html_entity_decode($subject)));
            $body = str_replace($item, $data[str_replace(array('{', '}'), '', $item)], stripslashes(html_entity_decode($body)));
        }
        // dd($variables);
        Config::set(['port' => env("MAIL_PORT"), 'host' => env("MAIL_HOST"), 'username' => env("MAIL_USERNAME"), 'password' => env("MAIL_PASSWORD")]);

        $sender = ['subject' => $subject, 'email' => $userEmail, 'from' => ['address' => 'dummy@gmail.com', 'name' => 'Empower Ed']];
        if (!empty($body) && !empty($userEmail)) {
            Mail::send('emails.default', ['body' => $body], function ($message) use ($sender, $data) {
                $message->to(
                    $sender['email']
                )
                    ->subject($sender['subject'])
                    ->from(
                        $sender['from']['address'],
                        $sender['from']['name']
                    );
                // Check if $data['pdf'] exists before attaching it
                if (isset($data['pdf'])) {
                    $message->attachData($data['pdf']->output(), "invoice.pdf");
                }
            });
        }
    }
}
// if (!function_exists('___mail_sender')) {
//     function ___mail_sender($email, $template_code, $data, $lan)
//     {
//         $template = EmailTemplate::where('variable_name', $template_code)
//             ->where('language', $lan)
//             ->first();

//         if (!empty($template)) {
//             $variables = explode(',', $template->variables);
//             $subject = $template->subject;
//             $body = $template->description;

//             foreach ($variables as $item) {
//                 $key = str_replace(['{', '}'], '', $item);
//                 $replacement = $data[$key] ?? '';
//                 $subject = str_replace($item, $replacement, stripslashes(html_entity_decode($subject)));
//                 $body = str_replace($item, $replacement, stripslashes(html_entity_decode($body)));
//             }

//             try {
//                 // Prepare optional attachment if exists
//                 $attachment = isset($data['pdf']) 
//                     ? [
//                         'file_name' => 'invoice.pdf',
//                         'file_url' => $data['pdf']->output() 
//                       ] 
//                     : null;
//                 $toAddresses = is_array($email) ? $email : [$email];
//                 $ccAddresses = ['admin@aeedison.com'];
//                 // Send email using Lambda API
//                 $response = sendEmailThrewLambdaApi(
//                     $toAddresses,
//                     $subject,
//                     $body,
//                     $attachment,
//                     $replyTo = 'admin@aeedison.com',
//                     $ccAddresses
//                 );

//                 // Log success for debugging
//                 Log::info('Email sent successfully via Lambda API', ['response' => $response,'email'=>$toAddresses]);

//             } catch (\Exception $e) {
//                 // Log any errors for debugging
//                 Log::error('Failed to send email via Lambda API', ['error' => $e->getMessage(),'email'=>$toAddresses]);
//             }
//         }
//     }
// }


if (!function_exists('uploadImage')) {
    function uploadImage($imageInfo, $folderName = '', $preImageName = '')
    {
        // dd($imageInfo);
        $imageName = '';
        if ($imageInfo->getClientOriginalName()) {
            $uploadFolder = "uploads";
            if ($folderName != '') {
                $uploadFolder .= '/' . $folderName;
            }
            $imageName = $preImageName . time() . '-' . $imageInfo->getClientOriginalName();
            $imageName = preg_replace('/[^A-Za-z0-9.]/', '-', $imageName);
            $imageInfo->move(public_path($uploadFolder), $imageName);
        }
        return $imageName;
    }
}

//unlink and upload new image in folder
if (!function_exists('unlinkImage')) {
    function unlinkImage($imageName, $newImageName, $folderName = '', $preImageName = '')
    {
        if ($imageName) {
            $uploadFolder = "uploads/";
            if ($folderName != '') {
                $uploadFolder .= $folderName . '/';
            }
            $newImageData =  $preImageName . time() . '-' . $newImageName->getClientOriginalName();
            // dd($newImageData);
            $newImageName->move(public_path($uploadFolder), $newImageData);
            $imageAbsolutePath = public_path($uploadFolder . $imageName);
            if (file_exists($imageAbsolutePath)) {
                unlink($imageAbsolutePath);
            }
            return $newImageData;
        }
    }
}
if (!function_exists('uploadImage')) {
    function uploadImage($imageInfo, $folderName = '', $preImageName = '')
    {
        // Check if imageInfo is not null and has a valid file
        if ($imageInfo && $imageInfo->getClientOriginalName()) {
            $uploadFolder = "uploads";
            if ($folderName != '') {
                $uploadFolder .= '/' . $folderName;
            }
            $imageName = $preImageName . time() . '-' . $imageInfo->getClientOriginalName();
            $imageName = preg_replace('/[^A-Za-z0-9.]/', '-', $imageName);
            $imageInfo->move(public_path($uploadFolder), $imageName);
            return $imageName;
        }
        return null;
    }
}


if (!function_exists('unlinkImage')) {
    function unlinkImage($imageName, $newImageName = null, $folderName = '', $preImageName = '')
    {
        if ($imageName) {
            $uploadFolder = "uploads/";
            if ($folderName != '') {
                $uploadFolder .= $folderName . '/';
            }
            $imageAbsolutePath = public_path($uploadFolder . $imageName);
            if (file_exists($imageAbsolutePath)) {
                unlink($imageAbsolutePath);
            }
            if ($newImageName) {
                $newImageData = $preImageName . time() . '-' . $newImageName->getClientOriginalName();
                $newImageData = preg_replace('/[^A-Za-z0-9.]/', '-', $newImageData);
                $newImageName->move(public_path($uploadFolder), $newImageData);
                return $newImageData;
            }
        }
        return null;
    }
}
// In a helper file or directly in your controller
function renderStars($rating)
{
    $fullStar = '<img src="' . asset('assets/images/star.png') . '" alt="Star"/>';
    $emptyStar = '<img src="' . asset('assets/images/star-empty.png') . '" alt="Star"/>';

    return str_repeat($fullStar, $rating) . str_repeat($emptyStar, 5 - $rating);
}

if (!function_exists('sectionOptions')) {
    function sectionOptions()
    {
        return [
            '' => 'Select',
            'performance' => 'Performance',
            'software' => 'Software',
            'design' => 'Design',
            // Add more options as needed
        ];
    }
}

if (!function_exists('translate')) {
    function translate($key, $language = null)
    {
        $language = $language ?? app()->getLocale();
        $translation = StaticContent::where('slug', $key)->where('language', $language)->first();
        // dd($translation);
        return $translation ? $translation->text : $key;
    }
}
function getSetting($title)
{
    $settingInfo = Setting::where('title', $title)->first();
    if (isset($settingInfo)) {
        return $settingInfo->value;
    }
    return '';
}

if (!function_exists('convertInCamelCase')) {
    function convertInCamelCase($string)
    {
        // Replace underscores with spaces
        $string = str_replace('_', ' ', $string);

        // Convert to title case
        return ucwords($string);
    }
}



function formatInitalDateRangetodMY()
{

    $today = Carbon::now();

    $startDate = $today->copy()->setYear(2024)->setMonth(1)->format('d/m/Y');
    $endDate = $today->copy()->format('d/m/Y');

    $startDate = Carbon::createFromFormat('d/m/Y', $startDate);
    $endDate = Carbon::createFromFormat('d/m/Y', $endDate);

    $startFormatted = $startDate->format('d-m-Y');
    $endFormatted = $endDate->format('d-m-Y');
    return $startFormatted . ' - ' . $endFormatted;
}

function sendEmailThrewLambdaApi($to, $subject, $message, $ccAddresses, $attachment = null, $replyTo = null)
{
    // Replace with your Lambda API endpoint
    $lambdaApiUrl = 'https://3d88ufplr2.execute-api.ap-southeast-1.amazonaws.com/new-stage-20241118/';
    $apiKey = 'RmaIFLb2w49gWd0bHM3PQ6dzHaY6rqfw8xawRceq';

    // Initialize cURL
    $ch = curl_init();

    // Set up HTTP headers
    $httpHeaders = [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey
    ];

    // Prepare email data
    $postData = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $replyTo
            ? "From: AENotes <admin@aeedison.com>|Reply-To: $replyTo"
            : "From: AENotes <admin@aeedison.com>",
        'attachments' => $attachment && isset($attachment['file_name'], $attachment['file_url'])
            ? [
                'file_name' => $attachment['file_name'],
                'file_url' => $attachment['file_url']
            ]
            : null,
        'cc' => $ccAddresses,
    ];

    // Convert data to JSON
    $data = json_encode($postData);

    // Configure cURL options
    curl_setopt($ch, CURLOPT_URL, $lambdaApiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute cURL request and capture the response
    $serverOutput = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new \Exception("cURL error: $error");
    }

    // Close the cURL session
    curl_close($ch);

    // Parse and return response
    $response = json_decode($serverOutput, true);
    if (isset($response['error'])) {
        throw new \Exception("Error from Lambda API: " . $response['error']);
    }

    return $response;
}
function ___carbon_now()
{
    $now = Carbon::now();
    return $now;
}
function getUser($id)
{
    $data = User::where('id', $id)->first();
    return $data;
}

function ___carbon_parse($data)
{
    $carbonParse = Carbon::parse($data);
    return $carbonParse;
}

function ___otp_code()
{
    return rand(1000, 9999);
}

// function ___sms_sender($to, $otp)
// {
//     $recipientNumber = $to;

//     try {
// $twilio = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));

// $contentVariables = [
//     "1" => (string)$otp,

// ];
// $message = $twilio->messages->create(
//     "whatsapp:$recipientNumber",
//     [
//         "from" => "whatsapp:+12408027582",
//         "contentSid" => "HX9e150710641837e51337b8f32f0c731b",
//         "contentVariables" => json_encode($contentVariables),
//     ]
// );
// function ___sms_sender($message, $recipients, $language)
// {
//     try {
//         $account_sid = getenv("TWILIO_SID");
//         $auth_token = getenv("TWILIO_AUTH_TOKEN");
//         $twilio_number = getenv("TWILIO_NUMBER");
//         $client = new Client($account_sid, $auth_token);
//         $client->messages->create($recipients, ['from' => $twilio_number, 'body' => $message]);
//     } catch (Exception $e) {
//         return response()->json([
//             // 'user_details' => (object)[],
//             'success' => true,
//             'message' => $language == 'english' ? 'OTP sent successfully!' : 'OTP 发送成功',
//         ], 200);
//     }
// }

function ___sms_sender($message, $recipients, $language = 'english')
{
    try {
        $client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $client->messages->create(
            $recipients,
            [
                'from' => config('services.twilio.from'),
                'body' => $message
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $language == 'english'
                ? 'OTP sent successfully!'
                : 'OTP 发送成功',
        ], 200);
    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

function getMenuData()
{

    if (in_array(2, explode(',', \Auth::user()->user_role_id))) {

        return AdminMenu::whereHas('PermissionUser', function ($q) {
            $q->whereUserId(\Auth::id());
        })->where('pid', 0)->get();
    }
    return AdminMenu::getMenuData();
}
function menu($id)
{

    if (in_array(2, explode(',', \Auth::user()->user_role_id))) {

        $menuId = AdminMenu::wherePid($id)->pluck("id");
        $getPermissionId = PermissionUsers::whereUserId(\Auth::id())->whereIn('menu_id', $menuId)->pluck('menu_id');
        $res = AdminMenu::whereIn('id', $getPermissionId)->get();
        return json_decode(json_encode($res), true);
    }
    return $data =  AdminMenu::getSubMenuData($id);
}

function _arrayfy($data = '')
{
    return json_decode(json_encode($data), true);
}

// Helper function to format avatar details
function formatAvatarPart($id, $avatarParts)
{
    if (!$id || !isset($avatarParts[$id])) {
        return null;
    }
    // return [
    //     'id' => (int)$id,
    //     'preview_image' => asset('assets/avtar/' . $avatarParts[$id]->preview_image),
    //     'apply_image' => asset('assets/avtar/' . $avatarParts[$id]->apply_image),
    //     'points' => $avatarParts[$id]->points
    // ];
    return [
        'id' => (int) $id,
        'preview_image' => getImagePathUrl($avatarParts[$id]->preview_image, 'assets/avtar'),
        'apply_image'   => getImagePathUrl($avatarParts[$id]->apply_image, 'assets/avtar'),
        'points'        => $avatarParts[$id]->points
    ];
}

function durationToSeconds($duration)
{
    $parts = explode(':', $duration);

    if (count($parts) === 2) {
        [$minutes, $seconds] = $parts;
        return ((int)$minutes * 60) + (int)$seconds;
    }

    // If only seconds are passed (e.g., "45")
    if (count($parts) === 1) {
        return (int)$parts[0];
    }

    // Invalid format, return 0 or handle differently
    return 0;
}


// Notification Configuration Functions
function sendNotification($user_id, $title, $message, $userData, $type)
{
    $data = [
        'title' => $title,
        'message' => $message,
        'type' => $type,
        'user_id' => $userData->id ?? null,
    ];
    $notificationData = array(
        'user_id' => $user_id,
        // 'driver_id' => $driver_id,
        'title' => $title,
        'body' => $message,
        'status' => 'unseen',
        'type' => $type,
        'data' => json_encode($data)
    );

    $notify = '';
    if ($type == 'child_support') {
        $notify = AppNotification::create($notificationData);
    }

    // $userData = json_encode($data);
    $badge = 0;

    $userToken = User::where('device_token', '!=', null)
        ->where('id', $user_id)
        ->value('device_token');
    $notificationData = [
        'notification' => [
            'title' => $title,
            'content' => $message,
            'type' => $type,
            'id' => "" . $notify ? $notify->id : '' . "",
            // 'booking_id' => $booking_id ?? '',
            'user_data' => $userData,
        ],
    ];
    $PostField = array(
        "message" => [
            "notification" => [
                "title" => $title,
                "body" => $message,
            ],
            "data" => [
                "title" => $title,
                "body" => $message,
                "badge" => "" . $badge . "",
                'id' => "" . $notify ? (string)$notify->id : '' . "",
                "type" => $type,
                // 'user_data' => json_encode($userData),
                "notification_data" => json_encode($notificationData),
            ],
            "token" => $userToken,
            "apns" => [
                "payload" => [
                    "aps" => [
                        "mutable-content" => 1,
                    ],
                ],

            ],
        ],
    );
    // }

    $accessToken = getAccessToken();
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/empowered-a0106/messages:send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($PostField),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ),
    ));
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);

    // dd($response, $error,$info);
    curl_close($curl);
}
function getAccessToken()
{
    $credentials = file_get_contents(public_path('/firebase/auth.json'));
    $credentials = json_decode($credentials, true);

    $url = "https://www.googleapis.com/oauth2/v4/token";
    $data = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => generateJwtAssertion($credentials),
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    $responseData = json_decode($response, true);
    return $responseData['access_token'];
}

function generateJwtAssertion($credentials)
{
    $jwtHeader = [
        'alg' => 'RS256',
        'typ' => 'JWT',
    ];
    $now = time();
    $jwtPayload = [
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://www.googleapis.com/oauth2/v4/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];
    $jwt = base64_encode(json_encode($jwtHeader)) . '.' . base64_encode(json_encode($jwtPayload));
    $signature = '';
    openssl_sign($jwt, $signature, $credentials['private_key'], 'SHA256');
    $jwt .= '.' . base64_encode($signature);
    return $jwt;
}

function sendNotificationToUsers(array $userIds, $title, $message, $userData, $type)
{
    foreach ($userIds as $user_id) {
        //         $notificationData = [
        //             'user_id' => $user_id,
        //             'title' => $title,
        //             'body' => $message,
        //             'status' => 'unseen',
        //             'type' => $type,
        //         ];

        //         $notify = null;
        //         if ($type == 'video_content') {
        //             $notify = Notification::create($notificationData);
        //         }

        //         $badge = 0;

        //         $userToken = User::where('device_token', '!=', null)
        //                          ->where('id', $user_id)
        //                          ->value('device_token');
        // // dd($userToken);
        //         if (!$userToken) {
        //             continue; // Skip users with no device token
        //         }

        //         $notificationPayload = [
        //             'notification' => [
        //                 'title' => $title,
        //                 'content' => $message,
        //                 'type' => $type,
        //                 'id' => $notify ? (string) $notify->id : '',
        //                 'user_data' => $userData,
        //             ],
        //         ];

        //         $PostField = [
        //             "message" => [
        //                 "notification" => [
        //                     "title" => $title,
        //                     "body" => $message,
        //                 ],
        //                 "data" => [
        //                     "title" => $title,
        //                     "body" => $message,
        //                     "badge" => (string) $badge,
        //                     "id" => $notify ? (string) $notify->id : '',
        //                     "type" => $type,
        //                     "notification_data" => json_encode($notificationPayload),
        //                 ],
        //                 "token" => $userToken,
        //                 "apns" => [
        //                     "payload" => [
        //                         "aps" => [
        //                             "mutable-content" => 1,
        //                         ],
        //                     ],
        //                 ],
        //             ],
        //         ];

        //         $accessToken = getAccessToken();

        //         $curl = curl_init();
        //         curl_setopt_array($curl, [
        //             CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/empowered-a0106/messages:send',
        //             CURLOPT_RETURNTRANSFER => true,
        //             CURLOPT_ENCODING => '',
        //             CURLOPT_MAXREDIRS => 10,
        //             CURLOPT_TIMEOUT => 0,
        //             CURLOPT_FOLLOWLOCATION => true,
        //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //             CURLOPT_CUSTOMREQUEST => 'POST',
        //             CURLOPT_POSTFIELDS => json_encode($PostField),
        //             CURLOPT_HTTPHEADER => [
        //                 'Content-Type: application/json',
        //                 'Authorization: Bearer ' . $accessToken,
        //             ],
        //         ]);
        //         // dd($PostField);

        //         curl_exec($curl);
        //         curl_close($curl);
        //     }
        $user = \App\Models\User::find($user_id);

        if (!$user) return;
        $notificationData = [
            'user_id' => $user_id,
            'title' => $title,
            'body' => $message,
            'status' => 'unseen',
            'type' => $type,
        ];

        if ($type == 'video_content') {
            AppNotification::create($notificationData);
        }
        $data = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'video_id' => $userData->id ?? null,
            'thumbnail' => $userData->thumbnail ?? null,
        ];
        // \Log::info('Sending notification to user: ' . $data);

        $user->notify(new CustomNotification($data));
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($menu_id, $type = 'view')
    {
        $user = auth('admin')->user();

        // User exist check
        if (!$user) return false;

        // Super Admin has all access
        if ($user->user_role_id == 1) return true;

        // Check in DB
        $query = PermissionUser::where('user_id', $user->id)
            ->where('menu_id', $menu_id);

        return $type === 'modify'
            ? $query->where('is_modify', 1)->exists()
            : $query->where('is_view', 1)->exists();
    }
}


// app/Helpers/VideoHelper.php
if (!function_exists('convertTimeToSeconds')) {
    function convertTimeToSeconds($time)
    {
        $parts = explode(':', $time);
        if (count($parts) == 2) { // MM:SS
            return ((int)$parts[0] * 60) + (int)$parts[1];
        } elseif (count($parts) == 3) { // HH:MM:SS
            return ((int)$parts[0] * 3600) + ((int)$parts[1] * 60) + (int)$parts[2];
        }
        return 0;
    }
}





if (!function_exists('getLocalizedCategoryName')) {
    function getLocalizedCategoryName($category, $language)
    {
        return $language === 'chinese'
            ? ($category->category_name_chinese ?? $category->category_name)
            : $category->category_name;
    }
}


function getNotificationContent($variable_name, $data = [])
{
    $tpl = NotificationTemplate::where('variable_name', $variable_name)->first();

    $subject     = $tpl->subject ?? '';
    $description = $tpl->description ?? '';

    // Replace placeholders like {mood_name}, {points}, etc.
    foreach ($data as $key => $value) {
        $subject     = str_replace('{' . $key . '}', $value, $subject);
        $description = str_replace('{' . $key . '}', $value, $description);
    }
    return [
        'title' => $subject,
        'body'  =>  $description,
        'type'  => $variable_name
    ];
}

// New Notification Function >>>>>>>>>>>>>>>>>>>>>>>>>>>>

function sendNotificationSender($user_id, $title, $message, $notification_type, $data = [], $user_type = 'user')
{
    try {
        $firebase = (new Factory())
            ->withServiceAccount(public_path('auth.json'));
        $messaging = $firebase->createMessaging();
        $notiArr['user_id'] = $user_id;
        $notiArr['title'] = $title;
        $notiArr['body'] =  $message;
        $notiArr['type'] = $notification_type;
        $notiArr['json_body'] =  $data;
        AppNotification::create($notiArr);
        // $badge=Notification::where(['status'=>'unread','user_id'=>$user_id])->count();
        $device = null;
        if ($user_type == "user") {
            $device = DeviceToken::where('user_id', $user_id)->first();
        }
        if ($device) {
            $device_id = $device->token;
            $fields = [
                'token' => $device_id,
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                    'sound' => 'default',
                    // 'badge' => $badge,
                    'data' => json_encode($data),
                ],
                // 'notification' => [
                //     'title' => $title,
                //     'body'  => $message,
                //     'sound' => 'default',
                // ],


                'data' => [
                    'title' => $title,
                    'body' => $message,
                    'type' => $notification_type,
                    'user_id' => $user_id,
                    'name' => $data['category'] ?? null,
                    'id' => $data['id'] ?? null,
                    'color' => $data['color'] ?? null,
                    'title_color' => $data['title_color'] ?? null,
                    // 'badge' => $badge,  
                    'data' => json_encode($data),
                    'sound' => 'default',
                ],
                // 'data' => array_merge($data, [
                //     'title' => $title,
                //     'body'  => $message,
                //     'type'  => $notification_type,
                //     'user_id' => $user_id,
                //     'sound' => 'default',
                // ]),
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $message,
                            ],
                            // 'badge' => $badge,
                            'sound' => 'default',
                        ],
                    ],
                ],
            ];

            $message = CloudMessage::fromArray($fields);
            $res = $messaging->send($message);
            return true;
            // return response()->json(['message' => 'Push notification sent successfully']);
        }
    } catch (MessagingException $e) {
        return response()->json(['error' => 'Failed to send notification: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}





// Battery Logic >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
if (! function_exists('battery_adjust')) {
    function battery_adjust(User $user, int $delta, string $reason, array $meta = [], ?Carbon $date = null): void
    {
        //Sirf child type users ke liye
        if ($user->user_type !== 'child') {
            return;
        }

        $dateStr = ($date ?? now())->toDateString();
        //Event log karo
        BatteryEvent::create([
            'user_id'        => $user->id,
            'direction'      => $delta >= 0 ? 'credit' : 'debit',
            'reason'         => $reason,
            'points'         => abs($delta),
            'effective_date' => $dateStr,
            'meta'           => $meta,
        ]);
        //Battery points adjust karo (0-100 ke beech me hi)
        $before = (int) ($user->battery_points ?? 0);
        $after  = max(0, min(100, $before + $delta));

        $user->battery_points = $after;
        $user->save();
    }
}
if (! function_exists('battery_credit_once_per_day')) {
    function battery_credit_once_per_day(User $user, int $points, string $reason, array $meta = [], ?Carbon $date = null): void
    {
        $d = ($date ?? now())->toDateString();
        $exists = BatteryEvent::where('user_id', $user->id)
            ->where('reason', $reason)
            ->where('direction', 'credit')
            ->whereDate('effective_date', $d)
            ->exists();
        if (!$exists) battery_adjust($user, +$points, $reason, $meta, $date ?? now());
    }
}

if (! function_exists('battery_debit_once_per_day')) {
    function battery_debit_once_per_day(User $user, int $points, string $reason, array $meta = [], ?Carbon $date = null): void
    {
        $d = ($date ?? now())->toDateString();

        //Check karo ki already penalty lagi hai ya nahi
        $exists = BatteryEvent::where('user_id', $user->id)
            ->where('reason', $reason)
            ->where('direction', 'debit')
            ->whereDate('effective_date', $d)
            ->exists();

        if (! $exists) {
            battery_adjust($user, -$points, $reason, $meta, $date ?? now());
        }
    }
}


function uploadFile($file, $folder, $dbFileName = null)
{
    if (!$file || !$file->isValid()) {
        return null;
    }
    $filename = time() . '-' . rand(10, 99) . '.' . $file->getClientOriginalExtension();
    $path = $folder . '/' . $filename;
    try {
        $oldPath = $folder . '/' . $dbFileName;
        if ($dbFileName && Storage::disk('s3')->exists($oldPath)) {
            Storage::disk('s3')->delete($oldPath);
        }
        Storage::disk('s3')->put($path, file_get_contents($file));
        Log::error('S3 upload success: hai');
        return $filename;
    } catch (\Exception $e) {
        Log::error('S3 upload failed: ' . $e->getMessage());
        return null;
    }
}

function getImagePathUrl($filename, $folder)
{
    if (!$filename) return null;
    $filename = ltrim($filename, '/');
    return Storage::disk('s3')->url($folder . '/' . $filename);
}
function getImageUrl($filename)
{
    try {
        if (Storage::disk('s3')->exists($filename)) {
            return Storage::disk('s3')->url($filename);
        }
        return asset($filename);
    } catch (\Exception $e) {
        return asset($filename);
    }
}
