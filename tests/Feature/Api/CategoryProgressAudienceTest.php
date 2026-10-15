<?php

namespace Tests\Feature\Api;

use App\Models\{Category, User, UserContentWatchHistory, VideoContent};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CategoryProgressAudienceTest extends TestCase
{
    use DatabaseTransactions;

    private function video(Category $category, string $audience): VideoContent
    {
        return VideoContent::create(['category_id' => $category->id, 'title' => 'Audience '.$audience,
            'description' => 'Test', 'user_type' => $audience, 'video_duration' => '10:00', 'status' => 'active']);
    }

    private function watched(User $user, VideoContent $video, string $duration): void
    {
        UserContentWatchHistory::create(['child_id' => $user->id, 'video_content_id' => $video->id,
            'last_watched_duration' => $duration, 'total_video_duration' => '10:00', 'is_completed' => 'no']);
    }

    private function percentageFor(User $user, Category $category): float
    {
        Passport::actingAs($user, [], 'api');
        $row = collect($this->postJson('/api/get-category', ['language' => 'english'])->assertOk()->json('data'))
            ->firstWhere('id', $category->id);
        $this->assertNotNull($row);

        return (float) $row['watched_percentage'];
    }

    public function test_parent_progress_counts_parent_and_both_videos_only(): void
    {
        $category = Category::create(['category_name' => 'Audience test category', 'status' => 'active']);
        $parentVideo = $this->video($category, 'parent');
        $bothVideo = $this->video($category, 'both');
        $childVideo = $this->video($category, 'child');
        $parent = User::factory()->parent()->create();

        // Half of the parent video out of parent + both (20 minutes).
        $this->watched($parent, $parentVideo, '10:00');
        $this->watched($parent, $childVideo, '10:00');
        $this->assertEqualsWithDelta(50.0, $this->percentageFor($parent, $category), 0.01);

        $this->watched($parent, $bothVideo, '10:00');
        $this->assertEqualsWithDelta(100.0, $this->percentageFor($parent, $category), 0.01);
    }

    public function test_child_progress_still_counts_child_and_both_videos(): void
    {
        $category = Category::create(['category_name' => 'Audience test category', 'status' => 'active']);
        $parentVideo = $this->video($category, 'parent');
        $this->video($category, 'both');
        $childVideo = $this->video($category, 'child');
        $child = User::factory()->create(['user_role_id' => 4, 'user_type' => 'child', 'status' => 'active']);

        $this->watched($child, $childVideo, '10:00');
        $this->watched($child, $parentVideo, '10:00');
        $this->assertEqualsWithDelta(50.0, $this->percentageFor($child, $category), 0.01);
    }
}
