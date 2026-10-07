<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class SchoolStudentCredentials extends Mailable
{
    public function __construct(public string $schoolName, public string $spreadsheet)
    {
    }

    public function build()
    {
        return $this->subject('Student credentials for '.$this->schoolName)
            ->html('<p>Student accounts have been created. The attached spreadsheet contains login details and password reset links (valid for 24 hours).</p>')
            ->attachData($this->spreadsheet, 'student-credentials.xlsx', [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
