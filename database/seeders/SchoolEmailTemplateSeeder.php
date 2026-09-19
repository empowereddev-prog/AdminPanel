<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Database\Seeders\Emails\BrandedEmailLayout as L;
use Illuminate\Database\Seeder;

/**
 * The six templates the school roster and child-seat flow needs.
 *
 * Two of them - signup_school_user and signup_teacher - already had call sites
 * in SchoolController and no template anywhere, so ___mail_sender was hitting
 * its silent "Mail skipped: missing template" return and every bulk-imported
 * parent and teacher was learning their generated password from nowhere.
 * Seeding these rows is what makes those two start delivering.
 *
 * Rows only, deliberately no Blade views: ___mail_sender prefers a view at
 * emails.{variable_name} over the database row, so shipping a view would make
 * the template permanently uneditable in the admin panel - and with
 * EmailTemplateController::create()/store() still stubs, that edit screen is
 * the only lever the team has.
 *
 * Re-running resets any admin edits to these six rows. Run it deliberately,
 * not as a routine deploy step.
 */
class SchoolEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $variableName => $template) {
            EmailTemplate::updateOrCreate(
                [
                    'variable_name' => $variableName,
                    'language' => 'english',
                ],
                [
                    'subject' => $template['subject'],
                    'variables' => $template['variables'],
                    'active' => 1,
                    'description' => $template['description'],
                ]
            );
        }
    }

    /**
     * Every token listed in `variables` MUST be supplied by every call site.
     * ___mail_sender substitutes $data[$key] ?? $data['otp'] ?? $data['code']
     * ?? '' - so an unsupplied token on a payload that happens to carry an OTP
     * renders that one-time code into the body. The token list and the caller's
     * $data array are one contract; SchoolEmailTemplateContractTest pins it.
     *
     * @return array<string,array{subject:string,variables:string,description:string}>
     */
    private function templates(): array
    {
        return [
            // Sent to the school's own contact address the moment the school is
            // created. Until now nothing at all was sent on onboarding: the
            // contact email was collected by store() and then never used, so a
            // school had no record of its own code, plan or limits.
            'school_onboarded' => [
                'subject' => 'Your EmpowerED school account is live — {school_name}',
                'variables' => '{school_name},{school_code},{subscription_type},{parent_limit},{child_seat_limit},{year}',
                'description' => L::wrap(
                    'Your school account is live',
                    L::p('<strong>{school_name}</strong> is now set up on EmpowerED. Here are the details your parents will need.')
                    . L::rows([
                        'School code' => '{school_code}',
                        'Plan' => '{subscription_type}',
                        'Parent accounts' => '{parent_limit}',
                        'Child places' => '{child_seat_limit}',
                    ])
                    . L::p('Share the school code only with the parents on your list. Each parent registers with the email address you gave us, and each address can be used once.')
                    . L::p('Parents you sent us have been emailed their own sign-in details separately. They add their children in the app, and each child uses one of your child places.', true)
                    . L::note('Need the code changed, or more places? Contact the EmpowerED team.'),
                    null,
                    'Welcome to EmpowerED'
                ),
            ],

            'signup_school_user' => [
                'subject' => 'Your EmpowerED account for {school_name}',
                'variables' => '{name},{email},{password},{school_name},{school_code},{year}',
                'description' => L::wrap(
                    'Your school has created your EmpowerED account',
                    L::p('Hello {name}, <strong>{school_name}</strong> has set up an EmpowerED account for you. Sign in with the details below and you can start adding your children straight away.')
                    . L::rows([
                        'Sign in with' => '{email}',
                        'School code' => '{school_code}',
                    ])
                    . L::credential('Temporary password', '{password}')
                    . L::p('Please change this password after your first sign-in. Sign in with the email address above - it is the address your school registered for you, and the app will not recognise a different one.', true)
                    . L::note('If you were not expecting this email, please contact your school.'),
                    null,
                    'Your school account is ready'
                ),
            ],

            'signup_teacher' => [
                'subject' => 'Your EmpowerED staff account for {school_name}',
                'variables' => '{name},{username},{password},{school_name},{year}',
                'description' => L::wrap(
                    'Your staff account is ready',
                    L::p('Hello {name}, <strong>{school_name}</strong> has created an EmpowerED staff account for you.')
                    . L::rows(['Username' => '{username}'])
                    . L::credential('Temporary password', '{password}')
                    . L::p('Please change this password after your first sign-in.', true)
                    . L::note('If you were not expecting this email, please contact your school.'),
                    null,
                    'Staff access'
                ),
            ],

            'school_parent_invited' => [
                'subject' => '{school_name} has invited you to EmpowerED',
                'variables' => '{name},{school_name},{school_code},{app_link},{year}',
                'description' => L::wrap(
                    '{school_name} has invited you to EmpowerED',
                    L::p('Hello {name}, your school has added you to its EmpowerED programme. Download the app and register using the school code below.')
                    . L::rows(['School code' => '{school_code}'])
                    . L::p('Register with <strong>the email address this invitation was sent to</strong>. Your school registered that address for you, and it is the only one that will be recognised for {school_name}.', true)
                    . L::note('This invitation is for you alone and cannot be used by anyone else.'),
                    ['label' => 'Get the app', 'url' => '{app_link}'],
                    'You have been invited'
                ),
            ],

            'school_offroster_attempt' => [
                'subject' => 'Someone tried to join {school_name} with your school code',
                'variables' => '{school_name},{attempt_count},{attempted_emails},{window},{year}',
                'description' => L::wrap(
                    'Your school code was used by someone not on your list',
                    L::p('In the last {window}, {attempt_count} sign-up attempt(s) used the school code for <strong>{school_name}</strong> from email addresses that are not on your parent list. Each one was blocked.')
                    . L::rows(['Attempts' => '{attempt_count}', 'Addresses' => '{attempted_emails}'])
                    . L::p('If these are parents who should have access, add them to your roster and we will let them in. If you do not recognise them, your school code may be circulating and we can issue you a new one.', true)
                    . L::note('You receive at most one of these notices per hour.'),
                    null,
                    'Roster alert'
                ),
            ],

            'school_seat_threshold' => [
                'subject' => '{school_name} has used {percent}% of its child places',
                'variables' => '{school_name},{seats_used},{seats_limit},{percent},{year}',
                'description' => L::wrap(
                    'Your child places are running low',
                    L::p('<strong>{school_name}</strong> has now used {percent}% of the child places on your plan.')
                    . L::rows([
                        'Places used' => '{seats_used}',
                        'Places on your plan' => '{seats_limit}',
                    ])
                    . L::p('Once every place is taken, parents will not be able to add further children until a place is freed or your plan is extended.', true),
                    null,
                    'Capacity notice'
                ),
            ],

            'school_import_summary' => [
                'subject' => 'Parent import complete for {school_name}',
                'variables' => '{school_name},{imported},{skipped},{failed},{seats_remaining},{year}',
                'description' => L::wrap(
                    'Your parent list has been imported',
                    L::p('The parent list for <strong>{school_name}</strong> has been processed.')
                    . L::rows([
                        'Accounts created' => '{imported}',
                        'Skipped (already registered)' => '{skipped}',
                        'Rejected (invalid details)' => '{failed}',
                        'Child places remaining' => '{seats_remaining}',
                    ])
                    . L::p('Everyone with a new account has been emailed their sign-in details.', true)
                    . L::note('Rejected rows usually have a malformed phone number, country code or email address.'),
                    null,
                    'Import report'
                ),
            ],
        ];
    }
}
