<?php

namespace App\Services;

use Illuminate\Mail\Message;

/**
 * Adds the "download the app" QR block to onboarding emails.
 *
 * Originally teacher-only (hence the name); the same block now also goes out
 * with the student credentials email and the parent school-code email, so the
 * set of templates that receive it lives in TEMPLATES.
 */
class TeacherSignupEmail
{
    public const DOWNLOAD_MARKER = '<!-- teacher-app-download -->';

    /** Templates (email_templates.variable_name) that carry the QR block. */
    public const TEMPLATES = [
        'signup_teacher',
        'school_student_credentials',
        'school_parent_code',
    ];

    public static function appliesTo(string $templateCode): bool
    {
        return in_array($templateCode, self::TEMPLATES, true);
    }

    public function addDownloadBlock(Message $message): void
    {
        $email = $message->getSymfonyMessage();
        $body = $email->getHtmlBody() ?? '';
        $block = view('emails.partials.teacher-app-download', [
            'message' => $message,
        ])->render();

        $email->html($this->place($body, $block));
    }

    /**
     * The same block for an in-browser preview. A cid: image only resolves
     * inside a mail client, so the QR is inlined as a data URI here.
     */
    public function previewHtml(string $body): string
    {
        $qr = 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents(resource_path('images/email/teacher-app-download-qr.jpeg')));
        $block = view('emails.partials.teacher-app-download', ['qrSrc' => $qr])->render();

        return $this->place($body, $block);
    }

    private function place(string $body, string $block): string
    {
        // New seeded templates place the block inside the branded card. Existing
        // database templates receive it too, without reseeding or overwriting edits.
        if (str_contains($body, self::DOWNLOAD_MARKER)) {
            return str_replace(self::DOWNLOAD_MARKER, $block, $body);
        }

        // Legacy templates are complete HTML documents. Appending after
        // </html> leaves content outside the document, where clients may
        // discard it. Keep the download section inside the body instead.
        if (preg_match('/<\/body\s*>/i', $body, $closing, PREG_OFFSET_CAPTURE)
            || preg_match('/<\/html\s*>/i', $body, $closing, PREG_OFFSET_CAPTURE)) {
            $offset = $closing[0][1];

            return substr($body, 0, $offset) . $block . substr($body, $offset);
        }

        return $body . $block;
    }
}
