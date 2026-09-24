<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\VideoContent;
use App\Services\DeepLinkService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Teachers browse Teen (child) content in-app; detail APIs reuse DeepLinkService
 * audience checks. Staff must be allowed on child-audience items; parents must not.
 */
class DeepLinkAudienceAccessTest extends TestCase
{
    use DatabaseTransactions;

    private function childVideo(): VideoContent
    {
        $category = Category::create([
            'category_name' => 'Teen Section ' . uniqid(),
            'status' => 'active',
        ]);

        return VideoContent::create([
            'category_id' => $category->id,
            'title' => 'Teen Video ' . uniqid(),
            'description' => 'Child-audience content for teachers to preview.',
            'status' => 'active',
            'user_type' => 'child',
            'age' => '11-18',
            'is_featured' => 'no',
            'points' => 0,
        ]);
    }

    public function test_teacher_can_resolve_child_audience_video(): void
    {
        $teacher = User::factory()->teacher()->create();
        $video = $this->childVideo();

        $result = app(DeepLinkService::class)->resolve('podcast', $video->id, $teacher, false);

        $this->assertSame(DeepLinkService::STATUS_OK, $result['status']);
        $this->assertSame(200, $result['http_status']);
    }

    public function test_parent_is_forbidden_from_child_audience_video(): void
    {
        $parent = User::factory()->parent()->create();
        $video = $this->childVideo();

        $result = app(DeepLinkService::class)->resolve('podcast', $video->id, $parent, false);

        $this->assertSame(DeepLinkService::STATUS_FORBIDDEN_ROLE, $result['status']);
        $this->assertSame(403, $result['http_status']);
    }

    public function test_video_content_details_allows_teacher_on_child_content(): void
    {
        $teacher = User::factory()->teacher()->create();
        $video = $this->childVideo();

        Passport::actingAs($teacher, [], 'api');

        $response = $this->postJson('/api/video-content-details', [
            'video_id' => $video->id,
            'language' => 'english',
        ]);

        $response->assertOk();
        $this->assertNotSame('forbidden_role', $response->json('deeplink_status'));
        $this->assertTrue($response->json('status'));
    }

    public function test_video_content_details_blocks_parent_on_child_content(): void
    {
        $parent = User::factory()->parent()->create([
            'school_id' => null,
        ]);
        $video = $this->childVideo();

        Passport::actingAs($parent, [], 'api');

        $response = $this->postJson('/api/video-content-details', [
            'video_id' => $video->id,
            'language' => 'english',
        ]);

        $response->assertStatus(403);
        $this->assertSame('forbidden_role', $response->json('deeplink_status'));
        $this->assertSame(
            'This content is not available for your account.',
            $response->json('message')
        );
    }
}
