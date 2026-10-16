<?php

namespace Database\Seeders\Emails;

use App\Support\Brand;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * Sent to each parent a school imports, with the temporary password the
 * import generated (SendStudentSignupMail). Deliberately carries no app QR:
 * StudentCredentialsEmailTest pins that this template stays out of
 * TeacherSignupEmail::TEMPLATES.
 */
class SchoolUserSignupTemplate
{
    public const VARIABLES = '{name},{email},{password},{school_name},{school_code},{year}';

    public const SUBJECT = 'Your ' . Brand::NAME . ' account for {school_name}';

    public static function html(): string
    {
        $body = L::p('Hello {name}, <strong>{school_name}</strong> has set up an ' . Brand::NAME . ' account for you. Sign in with the details below and you can start adding your children straight away.')
            . L::rows([
                'Sign in with' => '{email}',
                'School code' => '{school_code}',
            ])
            . L::credential('Temporary password', '{password}')
            . L::p('<strong>Getting started</strong>')
            . L::rows([
                'Step 1' => 'Download the ' . Brand::NAME . ' app',
                'Step 2' => 'Choose &ldquo;Parent&rdquo; on the login screen',
                'Step 3' => 'Sign in with the email address and temporary password above',
            ])
            . L::p('Please change your password after your first sign-in. Use the email address shown above, which is the one your school registered for you; the app will not recognise a different one.', true);

        return L::wrap(
            'Your school has created your account',
            $body . L::note('If you were not expecting this email, please contact your school.'),
            null,
            'Your school account is ready'
        );
    }
}
