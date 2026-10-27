<?php

namespace App\Support;

final class PushNotificationData
{
    /** Firebase requires every top-level data value to be a string. */
    public static function build($userId, $title, $body, $type, array $data = []): array
    {
        return [
            'title' => (string) $title,
            'body' => (string) $body,
            'type' => (string) $type,
            'user_id' => (string) $userId,
            'name' => (string) ($data['category'] ?? ''),
            'id' => (string) ($data['id'] ?? ''),
            'color' => (string) ($data['color'] ?? ''),
            'title_color' => (string) ($data['title_color'] ?? ''),
            // Keep nested IDs and points in their original types for the app.
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
            'sound' => 'default',
        ];
    }
}
