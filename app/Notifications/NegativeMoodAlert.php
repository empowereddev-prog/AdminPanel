<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NegativeMoodAlert extends Notification
{
    protected $child;
    protected $moods;
    protected $forParent;

    public function __construct($child, $moods, $forParent = false)
    {
        $this->child = $child;
        $this->moods = $moods;
        $this->forParent = $forParent;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $moodList = $this->moods->pluck('mood_name')->unique()->implode(', ');

        $msg = new MailMessage;
        $msg->subject('Negative Mood Alert')
            ->greeting('Hello!')
            ->line($this->forParent 
                ? "Your child {$this->child->name} has experienced negative moods in the last 5 days."
                : "You have shown signs of negative moods recently.")
            ->line("Moods detected: {$moodList}")
            ->line("Please take care and consider talking to someone if needed.");

        return $msg;
    }
}

