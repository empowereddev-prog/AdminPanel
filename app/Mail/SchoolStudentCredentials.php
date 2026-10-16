<?php

namespace App\Mail;

use App\Services\TeacherSignupEmail;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Message;
use Symfony\Component\Mime\Email;

/**
 * Sent to the school contact after a student import. The wording lives in the
 * editable `school_student_credentials` email template (admin > Email
 * Template); the app QR + website block is injected below. If that row is
 * missing the plain legacy text is used so the credentials still reach the
 * school.
 */
class SchoolStudentCredentials extends Mailable
{
    public function __construct(
        public string $schoolName,
        public string $spreadsheet,
        public int $studentCount = 0,
        public int $resetDays = 7,
    ) {
    }

    public function build()
    {
        $rendered = ___render_email_template('school_student_credentials', [
            'school_name' => $this->schoolName,
            'student_count' => (string) $this->studentCount,
            'reset_days' => (string) $this->resetDays,
            'year' => (string) date('Y'),
        ], 'english');

        if ($rendered['found'] && $rendered['body'] !== '') {
            $this->subject($rendered['subject'])
                ->html($rendered['body'])
                ->withSymfonyMessage(function (Email $email) {
                    app(TeacherSignupEmail::class)->addDownloadBlock(new Message($email));
                });
        } else {
            $this->subject('Student credentials for ' . $this->schoolName)
                ->html('<p>Student accounts have been created. The attached spreadsheet contains login details and password reset links (valid for ' . $this->resetDays . ' days).</p>');
        }

        return $this->attachData($this->spreadsheet, 'student-credentials.xlsx', [
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
