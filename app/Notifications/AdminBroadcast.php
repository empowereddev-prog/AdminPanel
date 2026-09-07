<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class AdminBroadcast extends Notification
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // use 'mail' too if needed
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->data['title'],
            'body' => $this->data['body'],
        ];
    }
}
