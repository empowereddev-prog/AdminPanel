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
                'subject' => \Database\Seeders\Emails\SchoolOnboardedTemplate::SUBJECT,
                'variables' => \Database\Seeders\Emails\SchoolOnboardedTemplate::VARIABLES,
                'description' => \Database\Seeders\Emails\SchoolOnboardedTemplate::html(),
            ],

            'signup_school_user' => [
                'subject' => \Database\Seeders\Emails\SchoolUserSignupTemplate::SUBJECT,
                'variables' => \Database\Seeders\Emails\SchoolUserSignupTemplate::VARIABLES,
                'description' => \Database\Seeders\Emails\SchoolUserSignupTemplate::html(),
            ],

            'signup_teacher' => [
                'subject' => 'Your EmpowerED staff account for {school_name}',
                'variables' => '{name},{username},{password},{school_name},{year}',
                'description' => \Database\Seeders\Emails\TeacherSignupTemplate::html(),
            ],

            // Sent to the school contact after the student import: the credentials
            // spreadsheet arrives as an attachment and the QR block is injected by
            // ___mail_sender (see TeacherSignupEmail::TEMPLATES).
            'school_student_credentials' => [
                'subject' => \Database\Seeders\Emails\StudentCredentialsTemplate::SUBJECT,
                'variables' => \Database\Seeders\Emails\StudentCredentialsTemplate::VARIABLES,
                'description' => \Database\Seeders\Emails\StudentCredentialsTemplate::html(),
            ],

            // Bulk "send the school code to parents" from School Details; carries
            // the app QR block (see TeacherSignupEmail::TEMPLATES).
            'school_parent_code' => [
                'subject' => \Database\Seeders\Emails\ParentSchoolCodeTemplate::SUBJECT,
                'variables' => \Database\Seeders\Emails\ParentSchoolCodeTemplate::VARIABLES,
                'description' => \Database\Seeders\Emails\ParentSchoolCodeTemplate::html(),
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
