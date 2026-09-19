<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\VideoUploadSignController;
use App\Jobs\NotifyVideoContentAudience;
use App\Models\User;
use App\Models\VideoContent;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Large videos used to be POSTed through nginx/PHP and came back as a 413
 * ("Payload too large"). They now go straight to S3 with the URLs signed by
 * VideoUploadSignController, and the form carries only the object key.
 */
class DirectVideoUploadTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create([
            'user_role_id' => 2,
            'user_type' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_guests_cannot_sign_an_upload(): void
    {
        $this->postJson(route('uploads.video.create'), [
            'filename' => 'clip.mp4',
            'size' => 1024,
        ])->assertStatus(401);
    }

    public function test_a_non_video_extension_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('uploads.video.create'), [
                'filename' => 'payload.php',
                'size' => 1024,
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Unsupported video type.']);
    }

    public function test_a_file_over_the_ceiling_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('uploads.video.create'), [
                'filename' => 'huge.mp4',
                'size' => VideoUploadSignController::MAX_BYTES + 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('size');
    }

    public function test_a_key_outside_the_tmp_prefix_cannot_be_signed(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('uploads.video.part'), [
                'key' => 'assets/video/live.mp4',
                'uploadId' => 'abc',
                'partNumber' => 1,
            ])
            ->assertStatus(422);
    }

    public function test_adopt_moves_the_object_out_of_tmp_and_drops_the_old_one(): void
    {
        Storage::fake('s3');
        $key = VideoUploadSignController::TMP_PREFIX . 'fresh.mp4';
        Storage::disk('s3')->put($key, 'new-bytes');
        Storage::disk('s3')->put('assets/video/old.mp4', 'old-bytes');

        $name = adoptUploadedObject($key, 'assets/video', 'old.mp4');

        $this->assertNotNull($name);
        $this->assertStringEndsWith('.mp4', $name);
        Storage::disk('s3')->assertExists('assets/video/' . $name);
        Storage::disk('s3')->assertMissing($key);
        Storage::disk('s3')->assertMissing('assets/video/old.mp4');
    }

    public function test_adopt_refuses_a_key_outside_the_tmp_prefix(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('assets/video/someone-elses.mp4', 'bytes');

        $this->assertNull(adoptUploadedObject('assets/video/someone-elses.mp4', 'assets/video'));
        Storage::disk('s3')->assertExists('assets/video/someone-elses.mp4');
    }

    public function test_adopt_returns_null_when_the_object_is_missing(): void
    {
        Storage::fake('s3');

        $this->assertNull(adoptUploadedObject(VideoUploadSignController::TMP_PREFIX . 'ghost.mp4', 'assets/video'));
    }

    public function test_adopt_keeps_the_old_file_when_nothing_replaces_it(): void
    {
        Storage::fake('s3');

        $this->assertNull(adoptUploadedObject(null, 'assets/video', 'old.mp4'));
        $this->assertNull(adoptUploadedObject('', 'assets/video', 'old.mp4'));
    }

    public function test_store_adopts_the_uploaded_key_and_keeps_the_browser_duration(): void
    {
        Storage::fake('s3');
        Queue::fake();
        $key = VideoUploadSignController::TMP_PREFIX . 'podcast.mp4';
        Storage::disk('s3')->put($key, 'video-bytes');

        $this->actingAs($this->admin(), 'admin')
            ->post(route('knowledge-base.store'), [
                'title' => 'A large podcast',
                'description' => 'Long enough description.',
                'category' => '1',
                'media_key' => $key,
                'media_duration' => '42:07',
                'is_featured' => 'no',
                'ratio_type' => 'landscape',
                'written_by' => 'Admin',
                'color' => '#ffffff',
                'title_color' => '#000000',
                'user_type' => 'parent',
            ])
            ->assertSessionHasNoErrors();

        $video = VideoContent::where('title', 'A large podcast')->firstOrFail();

        $this->assertNotNull($video->video_link);
        $this->assertSame('42:07', $video->video_duration);
        Storage::disk('s3')->assertExists('assets/video/' . $video->video_link);
        Storage::disk('s3')->assertMissing($key);
        Queue::assertPushed(NotifyVideoContentAudience::class);
    }

    public function test_the_blades_load_the_direct_uploader(): void
    {
        $views = [
            'admin/knowledge-base/create.blade.php',
            'admin/knowledge-base/edit.blade.php',
            'admin/knowledge-base-child/create.blade.php',
            'admin/knowledge-base-child/edit.blade.php',
            'admin/video-other/create.blade.php',
            'admin/video-other/edit.blade.php',
        ];

        foreach ($views as $view) {
            $blade = file_get_contents(resource_path('views/' . $view));
            $this->assertStringContainsString('direct-video-upload.js', $blade, $view);
            $this->assertStringContainsString("initDirectVideoUpload('#knowledgeForm')", $blade, $view);
        }
    }
}
