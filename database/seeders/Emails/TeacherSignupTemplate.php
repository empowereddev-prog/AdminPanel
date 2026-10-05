<?php

namespace Database\Seeders\Emails;

use App\Services\TeacherSignupEmail;

class TeacherSignupTemplate
{
    public static function html(): string
    {
        $body = <<<'HTML'
            <p style="margin:0 0 12px;color:#1c2a3a;font-size:16px;line-height:1.6;">Hello {name},</p>
            <p style="margin:0 0 24px;color:#4a5b6d;font-size:15px;line-height:1.7;"><strong style="color:#1c2a3a;">{school_name}</strong> has created your staff account. You're ready to get started with EmpowerED.</p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7fb;border:1px solid #d9e2ef;border-radius:12px;">
                <tr>
                    <td style="padding:22px 24px;">
                        <p style="margin:0 0 20px;color:#1a5edb;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Your sign-in details</p>
                        <p style="margin:0 0 6px;color:#4a5b6d;font-size:13px;">Username</p>
                        <p style="margin:0 0 20px;color:#1c2a3a;font-size:18px;font-weight:700;word-break:break-all;">{username}</p>
                        <p style="margin:0 0 6px;color:#4a5b6d;font-size:13px;">Password</p>
                        <p style="margin:0;color:#1a5edb;font-size:20px;font-weight:700;font-family:'Courier New',Courier,monospace;word-break:break-all;">{password}</p>
                    </td>
                </tr>
            </table>
HTML;

        return BrandedEmailLayout::wrap(
            'Welcome to EmpowerED',
            $body . TeacherSignupEmail::DOWNLOAD_MARKER
                . BrandedEmailLayout::note('If you were not expecting this email, please contact your school.'),
            null,
            'Your staff account is ready'
        );
    }
}
