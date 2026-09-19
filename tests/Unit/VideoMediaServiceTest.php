<?php

namespace Tests\Unit;

use App\Services\VideoMediaService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VideoMediaServiceTest extends TestCase
{
    public function test_probe_returns_zero_duration_when_ffmpeg_cannot_read_the_file(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'clip');
        file_put_contents($tmp, 'not a video');

        $file = new UploadedFile($tmp, 'clip.mp4', 'video/mp4', null, true);

        try {
            $result = (new VideoMediaService())->probe($file, 'clip.mp4', true);
        } finally {
            @unlink($tmp);
        }

        $this->assertSame('00:00', $result['duration']);
        $this->assertNull($result['thumbnail']);
    }
}
