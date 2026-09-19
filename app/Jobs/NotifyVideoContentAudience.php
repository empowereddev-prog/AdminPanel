<?php

namespace App\Jobs;

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

    /** A transient Firebase or SMTP blip should retry, not silently drop the batch. */
    public $tries = 3;

    /** Recipients are handled in batches so a whole-audience send stays bounded. */
    private const CHUNK = 500;

    /** @return array<int,int> seconds between retries */
    public function backoff(): array
    {
        return [30, 120];
    }

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

    /**
     * Unique recipients, not device rows.
     *
     * sendNotificationSender() takes a user_id and resolves that user's device
     * itself, so looping DeviceToken rows gave a three-device parent three
     * in-app rows and three pushes to the same handset, while a parent with no
     * token row got nothing at all - not even the in-app entry, which needs no
     * device. Public so the dedupe can be asserted without a Firebase client.
     *
     * @return array<int,int>
     */
    public function recipients(): array
    {
        return array_values(array_unique(array_map('intval', array_filter($this->userIds))));
    }

    public function handle(): void
    {
        $ids = $this->recipients();

        if ($ids === []) {
            return;
        }

        if ($this->mode === 'users') {
            sendNotificationToUsers($ids, $this->title, $this->body, $this->legacyUserData ?? $this->userData, $this->notificationType);

            return;
        }

        foreach (array_chunk($ids, self::CHUNK) as $batch) {
            foreach ($batch as $userId) {
                sendNotificationSender(
                    $userId,
                    $this->title,
                    $this->body,
                    $this->notificationType,
                    $this->userData,
                    $this->audienceType
                );
            }
        }
    }
}
