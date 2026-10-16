<?php

namespace Database\Seeders\Emails;

use App\Support\Brand;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * Sent to the school's own contact address as soon as the school is created
 * (SchoolNotifier::schoolOnboarded). It is the school's record of its code,
 * plan and limits, and the first explanation of how parents get in.
 */
class SchoolOnboardedTemplate
{
    public const VARIABLES = '{school_name},{school_code},{subscription_type},{parent_limit},{child_seat_limit},{year}';

    public const SUBJECT = 'Welcome to ' . Brand::NAME . ' — {school_name} is now live';

    public static function html(): string
    {
        $body = L::p('Welcome, and thank you for registering <strong>{school_name}</strong> with ' . Brand::NAME . '. Your school account is now live.')
            . L::credential('Your school code', '{school_code}')
            . L::p('Parents need this code to join your school in the app. Please share it only with the parents on your list.')
            . L::rows([
                'Plan' => '{subscription_type}',
                'Parent accounts' => '{parent_limit}',
                'Child places' => '{child_seat_limit}',
            ])
            . L::p('<strong>What happens next</strong>')
            . L::rows([
                'Step 1' => 'Share the school code with your parents',
                'Step 2' => 'Parents download the app and register with the email address you gave us',
                'Step 3' => 'Parents add their children, each using one child place',
            ])
            . L::p('Parents you sent us in your list are emailed their own sign-in details separately, so you do not need to pass those on.', true);

        return L::wrap(
            'Your school account is live',
            $body
                . L::note('Need the code changed, or more places? Contact the ' . Brand::NAME . ' team at <a href="mailto:info@empoweredhealth.asia" style="color:#1c2a3a;">info@empoweredhealth.asia</a>.'),
            null,
            'Welcome to ' . Brand::NAME
        );
    }
}
