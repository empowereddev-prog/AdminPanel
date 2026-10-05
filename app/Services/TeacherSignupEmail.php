<?php

namespace App\Services;

use Illuminate\Mail\Message;

class TeacherSignupEmail
{
    public const DOWNLOAD_MARKER = '<!-- teacher-app-download -->';

    public function addDownloadBlock(Message $message): void
    {
        $email = $message->getSymfonyMessage();
        $body = $email->getHtmlBody() ?? '';
        $block = view('emails.partials.teacher-app-download', [
            'message' => $message,
        ])->render();

        // New seeded templates place the block inside the branded card. Existing
        // database templates receive it too, without reseeding or overwriting edits.
        $email->html(str_contains($body, self::DOWNLOAD_MARKER)
            ? str_replace(self::DOWNLOAD_MARKER, $block, $body)
            : $body . $block);
    }
}
