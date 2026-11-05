<?php

namespace Database\Seeders\Emails;

use App\Services\TeacherSignupEmail;

/**
 * Sent to each independent student with their own username and temporary
 * password, on import (when the admin ticks the option) or when the admin
 * emails login details to students who haven't signed in yet.
 */
class StudentLoginTemplate
{
    public const VARIABLES = '{name},{username},{password},{school_name},{year}';

    public const SUBJECT = 'Your EmpowerED login for {school_name}';

    public static function html(): string
    {
        $body = <<<'HTML'
            <p style="margin:0 0 12px;color:#1c2a3a;font-size:16px;line-height:1.6;">Hello {name},</p>
            <p style="margin:0 0 24px;color:#4a5b6d;font-size:15px;line-height:1.7;"><strong style="color:#1c2a3a;">{school_name}</strong> has created your EmpowerED account. Here are your sign-in details.</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7fb;border:1px solid #d9e2ef;border-radius:12px;">
                <tr>
                    <td style="padding:22px 24px;">
                        <p style="margin:0 0 20px;color:#1a5edb;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Your sign-in details</p>
                        <p style="margin:0 0 6px;color:#4a5b6d;font-size:13px;">Username</p>
                        <p style="margin:0 0 20px;color:#1c2a3a;font-size:18px;font-weight:700;word-break:break-all;">{username}</p>
                        <p style="margin:0 0 6px;color:#4a5b6d;font-size:13px;">Temporary password</p>
                        <p style="margin:0;color:#1a5edb;font-size:20px;font-weight:700;font-family:'Courier New',Courier,monospace;word-break:break-all;">{password}</p>
                    </td>
                </tr>
            </table>
HTML;

        return BrandedEmailLayout::wrap(
            'Welcome to EmpowerED',
            $body
                . BrandedEmailLayout::rows([
                    'Step 1' => 'Download the EmpowerED app (scan the QR code below)',
                    'Step 2' => 'Choose &ldquo;Child&rdquo; on the login screen',
                    'Step 3' => 'Sign in with your username and temporary password',
                ])
                . TeacherSignupEmail::DOWNLOAD_MARKER
                . BrandedEmailLayout::note('Keep these details private. If you were not expecting this email, please contact your school.'),
            null,
            'Your student account is ready'
        );
    }
}
