<?php

namespace Tests\Feature\Api;

use App\Models\ChildMood;
use App\Models\Mood;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StoreChildMoodTest extends TestCase
{
    use DatabaseTransactions;

    private function child(): User
    {
        return User::create([
            'name' => 'Kid',
            'email' => 'k' . uniqid() . '@example.test',
            'password' => bcrypt('OldPass1@'),
            'user_type' => 'child',
            'user_role_id' => 4,
            'status' => 'active',
            'language' => 'english',
            'is_mood_updated' => 'no',
        ]);
    }

    private function mood(array $attrs = []): Mood
    {
        $payload = array_merge([
            'name' => 'Calm',
            'status' => 'active',
            'points' => 1,
        ], $attrs);

        if (Schema::hasColumn('moods', 'type') && !array_key_exists('type', $payload)) {
            $payload['type'] = 'positive';
        }

        return Mood::create($payload);
    }

    public function test_store_child_mood_succeeds_when_referred_video_is_null(): void
    {
        $child = $this->child();
        $moodAttrs = ['name' => 'Happy'];
        if (Schema::hasColumn('moods', 'referred_video')) {
            $moodAttrs['referred_video'] = null;
        }
        $mood = $this->mood($moodAttrs);

        Passport::actingAs($child, [], 'api');

        $response = $this->postJson('/api/store-child-mood', [
            'mood_id' => $mood->id,
            'mood_name' => $mood->name,
            'points' => 1,
            'language' => 'english',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', true);
        $this->assertTrue(
            ChildMood::where('child_id', $child->id)->where('mood_id', $mood->id)->exists()
        );
        $this->assertSame('yes', $child->fresh()->is_mood_updated);
        $this->assertIsArray($response->json('data.referred_video'));
    }

    public function test_store_child_mood_succeeds_when_referred_video_is_json(): void
    {
        if (!Schema::hasColumn('moods', 'referred_video')) {
            $this->markTestSkipped('moods.referred_video is not on this schema.');
        }

        $child = $this->child();
        $videos = [['id' => 99, 'title' => 'Clip']];
        $mood = $this->mood([
            'name' => 'Focused',
            'referred_video' => json_encode($videos),
        ]);

        Passport::actingAs($child, [], 'api');

        $response = $this->postJson('/api/store-child-mood', [
            'mood_id' => $mood->id,
            'mood_name' => $mood->name,
            'language' => 'english',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', true);
        $this->assertSame($videos, $response->json('data.referred_video'));
        $this->assertTrue(
            ChildMood::where('child_id', $child->id)->where('mood_id', $mood->id)->exists()
        );
    }

    public function test_store_child_mood_succeeds_with_empty_historical_negative_date(): void
    {
        $child = $this->child();
        $todayMood = $this->mood(['name' => 'Okay']);
        $negativeAttrs = ['name' => 'Sad'];
        if (Schema::hasColumn('moods', 'type')) {
            $negativeAttrs['type'] = 'negative';
        }
        $negative = $this->mood($negativeAttrs);

        ChildMood::create([
            'child_id' => $child->id,
            'mood_id' => $negative->id,
            'mood_name' => $negative->name,
            'points' => 0,
            'date' => Carbon::yesterday()->toDateString(),
        ]);

        if (Schema::hasColumn('child_moods', 'date')) {
            ChildMood::create([
                'child_id' => $child->id,
                'mood_id' => $negative->id,
                'mood_name' => $negative->name,
                'points' => 0,
                'date' => null,
            ]);
        }

        Passport::actingAs($child, [], 'api');

        $response = $this->postJson('/api/store-child-mood', [
            'mood_id' => $todayMood->id,
            'mood_name' => $todayMood->name,
            'language' => 'english',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', true);
        $this->assertTrue(
            ChildMood::where('child_id', $child->id)
                ->where('mood_id', $todayMood->id)
                ->whereDate('date', Carbon::today()->toDateString())
                ->exists()
        );
    }

    public function test_decode_referred_video_accepts_null_array_and_json(): void
    {
        $controller = $this->app->make(\App\Http\Controllers\MoodTrackerController::class);
        $method = new \ReflectionMethod($controller, 'decodeReferredVideo');

        $this->assertSame([], $method->invoke($controller, null));
        $this->assertSame([], $method->invoke($controller, ''));
        $this->assertSame([['id' => 1]], $method->invoke($controller, [['id' => 1]]));
        $this->assertSame([['id' => 1]], $method->invoke($controller, json_encode([['id' => 1]])));
    }

    public function test_mood_date_to_string_skips_empty_values(): void
    {
        $controller = $this->app->make(\App\Http\Controllers\MoodTrackerController::class);
        $method = new \ReflectionMethod($controller, 'moodDateToString');

        $this->assertNull($method->invoke($controller, null));
        $this->assertNull($method->invoke($controller, ''));
        $this->assertSame(
            Carbon::today()->toDateString(),
            $method->invoke($controller, Carbon::today())
        );
    }
}
