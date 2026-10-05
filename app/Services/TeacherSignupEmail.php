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
        if (str_contains($body, self::DOWNLOAD_MARKER)) {
            $body = str_replace(self::DOWNLOAD_MARKER, $block, $body);
        } elseif (preg_match('/<\/body\s*>/i', $body, $closing, PREG_OFFSET_CAPTURE)) {
            // Legacy templates are complete HTML documents. Appending after
            // </html> leaves content outside the document, where clients may
            // discard it. Keep the download section inside the body instead.
            $offset = $closing[0][1];
            $body = substr($body, 0, $offset) . $block . substr($body, $offset);
        } elseif (preg_match('/<\/html\s*>/i', $body, $closing, PREG_OFFSET_CAPTURE)) {
            $offset = $closing[0][1];
            $body = substr($body, 0, $offset) . $block . substr($body, $offset);
        } else {
            $body .= $block;
        }

        $email->html($body);
    }
}
