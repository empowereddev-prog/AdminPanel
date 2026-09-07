<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // Add 'mail' or 'broadcast' if needed
    }

    public function toDatabase($notifiable)
    {
        // dd($notifiable);
        return [
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'type' => $this->data['type'],
            'video_id' => $this->data['video_id'],
            'thumbnail' => $this->data['thumbnail'],
        ];
    }
}
