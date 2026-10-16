<?php

namespace Database\Seeders\Emails;

use App\Services\TeacherSignupEmail;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * Sent to parents in bulk from School Details > "Send the school code to
 * parents". It carries the school code, how to sign up, and the app QR.
 */
class ParentSchoolCodeTemplate
{
    public const VARIABLES = '{school_name},{school_code},{app_link},{year}';

    public const SUBJECT = 'Join {school_name} on EmpowerED';

    public static function html(): string
    {
        $body = L::p('Hello, <strong>{school_name}</strong> is using EmpowerED, a health and wellbeing app for teens. As a parent you can create your own free account with your school&#39;s code.')
            . L::rows(['School code' => '{school_code}'])
            . L::rows([
                'Step 1' => 'Download the EmpowerED app (scan the QR code below)',
                'Step 2' => 'Choose &ldquo;Parent&rdquo;, then &ldquo;Sign up&rdquo;',
                'Step 3' => 'Enter your details and the school code above',
                'Step 4' => 'Confirm the code we text to your mobile number',
            ])
            . L::p('You can also find the app and more information on the <a href="{app_link}" style="color:#1a5edb;font-weight:700;">EmpowerED website</a>.', true);

        return L::wrap(
            'Join {school_name} on EmpowerED',
            $body
                . TeacherSignupEmail::DOWNLOAD_MARKER
                . L::note('Please keep the school code private to parents at {school_name}. If you were not expecting this email, you can ignore it.'),
            null,
            'Your school code'
        );
    }
}
