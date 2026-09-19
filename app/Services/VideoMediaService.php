<?php

namespace App\Services;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VideoMediaService
{
    /**
     * Duration + optional first-frame thumbnail. Failures must not abort the
     * HTTP request: missing ffmpeg or a bad file still leaves duration 00:00.
     *
     * @return array{duration:string,thumbnail:?string}
     */
    public function probe(UploadedFile $file, string $uploadedBasename, bool $generateThumbnail): array
    {
        $result = ['duration' => '00:00', 'thumbnail' => null];
        $path = $file->getRealPath();

        if (!$path || !is_file($path)) {
            return $result;
        }

        try {
            $ffprobe = FFProbe::create(['timeout' => 30]);
            $seconds = $ffprobe->format($path)->get('duration');
            if ($seconds !== null) {
                $result['duration'] = sprintf('%02d:%02d', floor((float) $seconds / 60), (int) ((float) $seconds % 60));
            }

            if ($generateThumbnail) {
                $ffmpeg = FFMpeg::create(['timeout' => 30]);
                $thumbName = pathinfo($uploadedBasename, PATHINFO_FILENAME) . '.jpg';
                $tmpThumb = sys_get_temp_dir() . '/' . $thumbName;
                $ffmpeg->open($path)->frame(TimeCode::fromSeconds(1))->save($tmpThumb);

                if (is_file($tmpThumb)) {
                    $thumb = new UploadedFile($tmpThumb, $thumbName, 'image/jpeg', null, true);
                    $result['thumbnail'] = uploadFile($thumb, 'assets/images');
                    @unlink($tmpThumb);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Video probe/ffmpeg failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }
}
