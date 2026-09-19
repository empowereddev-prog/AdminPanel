<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Presigned multipart uploads for admin video files.
 *
 * Large podcasts used to be POSTed through nginx/PHP and died on
 * client_max_body_size with a 413 ("Payload too large") before Laravel ever
 * booted. The browser now PUTs the bytes straight to S3 with the URLs signed
 * here and only sends us the resulting object key. See docs/DIRECT_S3_UPLOAD.md.
 */
class VideoUploadSignController extends Controller
{
    /** Everything signed here lands under this prefix and nowhere else. */
    public const TMP_PREFIX = 'assets/video/tmp/';

    public const MAX_BYTES = 2147483648; // 2 GiB

    private const ALLOWED_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'ogg', 'qt'];

    private const URL_TTL = '+1 hour';

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filename' => 'required|string|max:255',
            'size' => 'required|integer|min:1|max:' . self::MAX_BYTES,
            'content_type' => 'nullable|string|max:255',
        ]);

        $extension = strtolower((string) pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return response()->json(['message' => 'Unsupported video type.'], 422);
        }

        $key = self::TMP_PREFIX . Str::uuid() . '.' . $extension;

        try {
            $result = $this->client()->createMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $key,
                'ContentType' => $data['content_type'] ?: 'application/octet-stream',
            ]);
        } catch (\Throwable $e) {
            return $this->failed('createMultipartUpload', $e);
        }

        return response()->json([
            'key' => $key,
            'uploadId' => $result['UploadId'],
            'partSize' => 16 * 1024 * 1024,
        ]);
    }

    public function part(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|max:255',
            'uploadId' => 'required|string|max:255',
            'partNumber' => 'required|integer|min:1|max:10000',
        ]);

        if (!$this->keyIsOurs($data['key'])) {
            return response()->json(['message' => 'Invalid upload key.'], 422);
        }

        try {
            $client = $this->client();
            $command = $client->getCommand('UploadPart', [
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
                'PartNumber' => $data['partNumber'],
            ]);

            $url = (string) $client->createPresignedRequest($command, self::URL_TTL)->getUri();
        } catch (\Throwable $e) {
            return $this->failed('UploadPart presign', $e);
        }

        return response()->json(['url' => $url]);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|max:255',
            'uploadId' => 'required|string|max:255',
            'parts' => 'required|array|min:1',
            'parts.*.PartNumber' => 'required|integer|min:1|max:10000',
            'parts.*.ETag' => 'required|string|max:255',
        ]);

        if (!$this->keyIsOurs($data['key'])) {
            return response()->json(['message' => 'Invalid upload key.'], 422);
        }

        $parts = collect($data['parts'])
            ->sortBy('PartNumber')
            ->map(fn ($part) => ['PartNumber' => (int) $part['PartNumber'], 'ETag' => $part['ETag']])
            ->values()
            ->all();

        try {
            $this->client()->completeMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
                'MultipartUpload' => ['Parts' => $parts],
            ]);
        } catch (\Throwable $e) {
            return $this->failed('completeMultipartUpload', $e);
        }

        return response()->json(['key' => $data['key']]);
    }

    public function abort(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'required|string|max:255',
            'uploadId' => 'required|string|max:255',
        ]);

        if (!$this->keyIsOurs($data['key'])) {
            return response()->json(['message' => 'Invalid upload key.'], 422);
        }

        try {
            $this->client()->abortMultipartUpload([
                'Bucket' => $this->bucket(),
                'Key' => $data['key'],
                'UploadId' => $data['uploadId'],
            ]);
        } catch (\Throwable $e) {
            // Nothing the admin can do about a failed abort; the bucket
            // lifecycle rule cleans stale parts up anyway.
            Log::warning('abortMultipartUpload failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    private function keyIsOurs(string $key): bool
    {
        return str_starts_with($key, self::TMP_PREFIX) && !str_contains($key, '..');
    }

    private function client()
    {
        return Storage::disk('s3')->getClient();
    }

    private function bucket(): string
    {
        return (string) config('filesystems.disks.s3.bucket');
    }

    private function failed(string $op, \Throwable $e): JsonResponse
    {
        Log::error('S3 multipart ' . $op . ' failed: ' . $e->getMessage());

        return response()->json(['message' => 'Could not start the direct upload.'], 500);
    }
}
