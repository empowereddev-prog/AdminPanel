<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Push + in-app notifications after a podcast/video is saved. Must not run
 * inside the upload HTTP request: per-user Firebase clients are what drop
 * the connection after progress hits 100%.
 */
class NotifyVideoContentAudience implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public string $notificationType,
        public array $userData,
        public string $audienceType = 'user',
        public string $mode = 'sender',
        public mixed $legacyUserData = null,
    ) {
    }

    public function handle(): void
    {
        $ids = array_values(array_filter($this->userIds));
        if ($ids === []) {
            return;
        }

        if ($this->mode === 'users') {
            sendNotificationToUsers($ids, $this->title, $this->body, $this->legacyUserData ?? $this->userData, $this->notificationType);

            return;
        }

        $tokens = DeviceToken::whereIn('user_id', $ids)
            ->whereNotNull('token')
            ->get();

        foreach ($tokens as $row) {
            sendNotificationSender(
                $row->user_id,
                $this->title,
                $this->body,
                $this->notificationType,
                $this->userData,
                $this->audienceType
            );
        }
    }
}
