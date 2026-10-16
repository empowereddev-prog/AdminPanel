<?php

namespace Database\Seeders\Emails;

use App\Services\TeacherSignupEmail;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * The email the school receives after importing its students. It carries the
 * credentials spreadsheet as an attachment, so the body explains how the school
 * hands the logins out and how students get the app (QR + website link).
 */
class StudentCredentialsTemplate
{
    public const VARIABLES = '{school_name},{student_count},{reset_days},{year}';

    public const SUBJECT = 'Student accounts are ready for {school_name}';

    public static function html(): string
    {
        $body = L::p('Hello, the EmpowerED student accounts for <strong>{school_name}</strong> have been created ({student_count} students).')
            . L::p('The attached spreadsheet, <strong>student-credentials.xlsx</strong>, lists each student&#39;s username, temporary password and a personal password reset link. Please share each student&#39;s details with them privately.')
            . L::rows([
                'Step 1' => 'Download the EmpowerED app (scan the QR code below)',
                'Step 2' => 'Choose &ldquo;Child&rdquo; on the login screen',
                'Step 3' => 'Sign in with the username and temporary password',
            ])
            . L::p('Students can also choose their own password at any time using the reset link in the spreadsheet. Reset links stay valid for {reset_days} days.', true);

        return L::wrap(
            'Your student accounts are ready',
            $body
                . TeacherSignupEmail::DOWNLOAD_MARKER
                . L::note('The spreadsheet contains passwords, so please keep it private and delete it once the logins have been shared. If you were not expecting this email, please contact EmpowerED.'),
            null,
            'Student accounts'
        );
    }
}
