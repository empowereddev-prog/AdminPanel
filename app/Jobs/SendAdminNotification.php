<?php

// app/Jobs/SendAdminNotification.php

namespace App\Jobs;

use App\Models\User;
use App\Models\AdminNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAdminNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user_id;
    protected $title;
    protected $message;
    protected $type;

    public function __construct($user_id, $title, $message,$type)
    {
        $this->user_id = $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
    }

    public function handle()
    {
        // dd($this);
        $user = \App\Models\User::find($this->user_id);
        if ($user) {

            $data = [
                'title' => $this->title,
                'message' => $this->message,
                'type' => $this->type,
                'user_id' => $this->user_id ?? null,
            ];
            // dd($data);
            $notificationData = array(
                'user_id' => $this->user_id,
                // 'driver_id' => $driver_id,
                'title' => $this->title,
                'body' => $this->message,
                'status' => 'unseen',
                // 'type' => $type,
                'data' => json_encode($data)
            );
            // dd($notificationData);
            
            $notify = '';
            // if($type == 'child_support' ){
                $notify = AdminNotification::where('user_id',$this->user_id)->where('title',$this->title)->update([
                    'is_sent' => 1
                ]);
                // dd($notify);
            // }
        
            $userData = json_encode($data);
            $badge = 0;
        // dd($this->user_id);
            $userToken = User::where('device_token', '!=', null)
                             ->where('id', (int)$this->user_id)
                             ->value('device_token');
                            //  dd($userToken);
            $notificationData = [
                'notification' => [
                    'title' => $this->title,
                    'content' => $this->message,
                    'type' => $this->type,
                    'id' => "".$notify ? $notify->id : ''."",
                    // 'booking_id' => $booking_id ?? '',
                    'user_data' => $userData,
                ],
            ];
                $PostField = array(
                    "message" => [
                        "notification" => [
                            "title" => $this->title,
                            "body" => $this->message,
                        ],
                        "data" => [
                            "title" => $this->title,
                            "body" => $this->message,
                            "badge" => "" . $badge . "",
                            'id' => "". $notify ? (string)$notify->id : '' ."",
                            "type" => $this->type,
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
            $credentials = file_get_contents(storage_path('app/firebase/auth-a0106.json'));
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
            $accessToken = $responseData['access_token'];
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
        
            dd($response, $error,$info);
            curl_close($curl);
            // Example log. Replace with email, push, or other logic
            \Log::info("Notification sent to User ID {$user->id}: {$this->title} - {$this->message}");
        }
    }
}
