<?php

namespace Tests\Feature;

use App\Jobs\NotifyVideoContentAudience;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PodcastUploadTimeoutTest extends TestCase
{
    public function test_upload_file_helper_streams_to_s3(): void
    {
        $source = file_get_contents(base_path('app/helper.php'));
        $fn = substr($source, strpos($source, 'function uploadFile'));

        $this->assertStringContainsString("putFileAs(\$folder, \$file, \$filename)", $fn);
        $this->assertStringNotContainsString('file_get_contents($file)', $fn);
    }

    public function test_upload_file_replaces_the_old_object_only_after_put_succeeds(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('assets/video/old.mp4', 'previous');

        $name = uploadFile(UploadedFile::fake()->create('clip.mp4', 20), 'assets/video', 'old.mp4');

        $this->assertNotNull($name);
        Storage::disk('s3')->assertExists('assets/video/' . $name);
        Storage::disk('s3')->assertMissing('assets/video/old.mp4');
    }

    public function test_upload_file_skips_put_when_the_new_file_matches_the_old_object(): void
    {
        Storage::fake('s3');
        $bytes = 'same-video-bytes';
        Storage::disk('s3')->put('assets/video/old.mp4', $bytes);

        $path = tempnam(sys_get_temp_dir(), 'clip');
        file_put_contents($path, $bytes);
        $file = new UploadedFile($path, 'clip.mp4', 'video/mp4', null, true);

        try {
            $name = uploadFile($file, 'assets/video', 'old.mp4');
        } finally {
            @unlink($path);
        }

        $this->assertSame('old.mp4', $name);
        Storage::disk('s3')->assertExists('assets/video/old.mp4');
        $this->assertSame(['assets/video/old.mp4'], Storage::disk('s3')->files('assets/video'));
    }

    public function test_upload_file_null_still_deletes_the_named_object(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('assets/images/gone.jpg', 'thumb');

        $this->assertNull(uploadFile(null, 'assets/images', 'gone.jpg'));
        Storage::disk('s3')->assertMissing('assets/images/gone.jpg');
    }

    public function test_create_blades_do_not_claim_a_jquery_timeout(): void
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
            $this->assertStringNotContainsString(
                'Network timeout occurred during data sync.',
                $blade,
                $view
            );
            $this->assertStringContainsString('xhr.status === 0', $blade, $view);
            $this->assertStringContainsString(
                'Connection lost before the server responded',
                $blade,
                $view
            );
        }
    }

    public function test_notify_video_content_audience_is_queueable(): void
    {
        Queue::fake();

        NotifyVideoContentAudience::dispatch(
            [1, 2],
            'Title',
            'Body',
            'video_content',
            ['id' => 9]
        );

        Queue::assertPushed(NotifyVideoContentAudience::class, function (NotifyVideoContentAudience $job) {
            return $job->userIds === [1, 2]
                && $job->notificationType === 'video_content'
                && $job->mode === 'sender';
        });
    }
}
