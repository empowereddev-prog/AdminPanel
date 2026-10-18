<?php

namespace Database\Seeders\Emails;

use App\Services\TeacherSignupEmail;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * The roster invitation sent to one parent from the school screen. It carries
 * the school code, the app QR and a "Get the app" button that opens the
 * self-hosted download page ({app_link}).
 */
class ParentInvitedTemplate
{
    public const VARIABLES = '{name},{school_name},{school_code},{app_link},{year}';

    public const SUBJECT = '{school_name} has invited you to EmpowerED';

    public static function html(): string
    {
        return L::wrap(
            '{school_name} has invited you to EmpowerED',
            L::p('Hello {name}, your school has added you to its EmpowerED programme. Download the app and register using the school code below.')
            . L::rows(['School code' => '{school_code}'])
            . L::p('Register with <strong>the email address this invitation was sent to</strong>. Your school registered that address for you, and it is the only one that will be recognised for {school_name}.', true)
            . TeacherSignupEmail::DOWNLOAD_MARKER
            . L::note('This invitation is for you alone and cannot be used by anyone else.'),
            ['label' => 'Get the app', 'url' => '{app_link}'],
            'You have been invited'
        );
    }
}
