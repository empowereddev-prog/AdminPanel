<?php

namespace Tests\Unit\Support;

use App\Support\PushNotificationData;
use Kreait\Firebase\Messaging\CloudMessage;
use PHPUnit\Framework\TestCase;

class PushNotificationDataTest extends TestCase
{
    public function test_child_notification_is_accepted_by_firebase_with_an_integer_parent_id(): void
    {
        $extra = ['child_id' => 1116, 'battery_points' => 100, 'type' => 'child_add'];
        $message = CloudMessage::fromArray([
            'token' => 'test-device-token',
            'data' => PushNotificationData::build(1115, 'Child added', '100% battery', 'child_add', $extra),
        ])->jsonSerialize();
        $payload = json_decode(json_encode($message, JSON_THROW_ON_ERROR), true)['data'];

        $this->assertSame('1115', $payload['user_id']);
        $this->assertSame('', $payload['id']);
        $this->assertSame('', $payload['name']);
        $this->assertSame('', $payload['color']);
        $this->assertSame('', $payload['title_color']);
        $this->assertSame($extra, json_decode($payload['data'], true));
        foreach ($payload as $value) {
            $this->assertIsString($value);
        }
    }

    public function test_content_notification_keeps_its_metadata_as_strings(): void
    {
        $extra = ['id' => 42, 'category' => 'Breathing', 'color' => '#fff', 'title_color' => '#000'];
        $message = CloudMessage::fromArray([
            'data' => PushNotificationData::build(1115, 'Title', 'Body', 'content', $extra),
        ]);
        $payload = json_decode(json_encode($message, JSON_THROW_ON_ERROR), true)['data'];

        $this->assertSame('42', $payload['id']);
        $this->assertSame('Breathing', $payload['name']);
        $this->assertSame('#fff', $payload['color']);
        $this->assertSame('#000', $payload['title_color']);
        $this->assertSame($extra, json_decode($payload['data'], true));
    }
}
