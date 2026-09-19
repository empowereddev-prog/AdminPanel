<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Database\Seeders\Emails\BrandedEmailLayout as L;
use Illuminate\Database\Seeder;

/**
 * Account-lifecycle email for the consumer app.
 *
 * `delete_account` had a call site in HomeApiController::deleteUser() and no
 * template row anywhere in this repo - the same gap signup_school_user had.
 * Wherever a row was hand-added in production it declared a token the caller
 * does not pass, which is why the delivered copy read "associated with the
 * email  has been delete": ___mail_sender resolves an unsupplied token to '',
 * so the address vanished rather than failing loudly.
 *
 * Row only, no Blade view: ___mail_sender prefers a view at
 * emails.{variable_name} over the database row, and a view would make this
 * permanently uneditable in the admin panel.
 *
 * Re-running resets any admin edits to this row. Run it deliberately.
 */
class AccountEmailTemplateSeeder extends Seeder
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
     * Every token listed in `variables` MUST be supplied by every call site -
     * see the note on SchoolEmailTemplateSeeder::templates(). Here that is
     * HomeApiController::deleteUser(), pinned by
     * SchoolEmailTemplateContractTest.
     *
     * @return array<string,array{subject:string,variables:string,description:string}>
     */
    private function templates(): array
    {
        return [
            // Sent as the account is soft-deleted, so it is the last thing the
            // user hears from us - it has to name the address it acted on.
            'delete_account' => [
                'subject' => 'Your EmpowerED account has been deleted',
                'variables' => '{name},{email},{year}',
                'description' => L::wrap(
                    'Your account has been deleted',
                    L::p('Hello {name}, the EmpowerED account registered to <strong>{email}</strong> has been deleted, along with any child profiles it held.')
                    . L::p('This cannot be undone. You are welcome to register again from the app at any time, though the previous account\'s content will not come back.', true)
                    . L::note('If you did not ask for this, contact us at <a href="mailto:info@empoweredhealth.asia" style="color:#1c2a3a;">info@empoweredhealth.asia</a> straight away.'),
                    null,
                    'Sorry to see you go'
                ),
            ],
        ];
    }
}
