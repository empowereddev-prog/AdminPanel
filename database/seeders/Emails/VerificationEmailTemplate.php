<?php

namespace Database\Seeders\Emails;

use App\Support\Brand;
use Database\Seeders\Emails\BrandedEmailLayout as L;

/**
 * The link a parent gets right after registering (and again each time they try
 * to sign in with an unverified email). Sent by RegisterService::register and
 * HomeApiController::login - both must supply every token in VARIABLES.
 */
class VerificationEmailTemplate
{
    public const VARIABLES = '{name},{email},{link},{year}';

    public const SUBJECT = 'Verify your ' . Brand::NAME . ' email address';

    public static function html(): string
    {
        $body = L::p('Hello {name}, welcome to ' . Brand::NAME . '! Please confirm that <strong>{email}</strong> is your email address to finish setting up your account.')
            . L::p('Select the button below to verify your email. You will need to do this before you can sign in to the app.')
            . L::button('Verify my email', '{link}');

        return L::wrap(
            'Verify your email address',
            $body
                . '<p style="margin:20px 0 0;color:#8a97a6;font-size:13px;line-height:1.6;">If the button does not work, copy and paste this link into your browser:<br><a href="{link}" style="color:#1a5edb;word-break:break-all;">{link}</a></p>'
                . L::note('If you did not create an ' . Brand::NAME . ' account, you can safely ignore this email.'),
            null,
            'Confirm your email'
        );
    }
}
