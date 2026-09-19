<?php

namespace Tests\Feature\School;

use App\Jobs\SendStudentSignupMail;
use App\Models\EmailTemplate;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use App\Services\School\SchoolNotifier;
use Database\Seeders\AccountEmailTemplateSeeder;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * ___mail_sender substitutes a token as:
 *
 *     $data[$key] ?? $data['otp'] ?? $data['code'] ?? ''
 *
 * so a token a template declares but its caller does not supply does not render
 * blank - it renders whatever one-time code happens to be in the payload. The
 * `variables` column and the call site's $data array are therefore one contract,
 * and this is what holds them together.
 *
 * These render through the real mail path and read the message off the array
 * transport, so they fail on a template that was edited to add a token nobody
 * passes.
 */
class SchoolEmailTemplateContractTest extends TestCase
{
    use DatabaseTransactions;

    private const TEMPLATES = [
        'delete_account',
        'signup_school_user',
        'signup_teacher',
        'school_parent_invited',
        'school_offroster_attempt',
        'school_seat_threshold',
        'school_import_summary',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        (new SchoolEmailTemplateSeeder())->run();
        (new AccountEmailTemplateSeeder())->run();
        $this->transport()->flush();
    }

    private function transport()
    {
        return Mail::mailer()->getSymfonyTransport();
    }

    /** @return array{subject:string,body:string} */
    private function lastMessage(): array
    {
        $messages = collect($this->transport()->messages());

        $this->assertTrue($messages->isNotEmpty(), 'No mail was sent - the template resolved to nothing.');

        $sent = $messages->last()->getOriginalMessage();

        return [
            'subject' => $sent->getSubject() ?? '',
            'body' => $sent->getHtmlBody() ?? $sent->getTextBody() ?? '',
        ];
    }

    private function assertFullyRendered(string $template): void
    {
        $message = $this->lastMessage();
        $combined = $message['subject'] . $message['body'];

        preg_match_all('/\{[a-z_]+\}/', $combined, $matches);

        $this->assertSame(
            [],
            array_values(array_unique($matches[0])),
            "Template '{$template}' rendered with unsubstituted tokens - its call site is missing keys."
        );

        $this->assertNotEmpty($message['subject'], "Template '{$template}' sent with an empty subject.");
    }

    public function test_every_template_is_seeded_and_active(): void
    {
        foreach (self::TEMPLATES as $name) {
            $row = EmailTemplate::where('variable_name', $name)->where('language', 'english')->first();

            $this->assertNotNull($row, "Template '{$name}' is not seeded - ___mail_sender would silently send nothing.");
            $this->assertNotEmpty($row->description, "Template '{$name}' has an empty body.");
            $this->assertNotEmpty($row->variables, "Template '{$name}' declares no variables.");
        }
    }

    /**
     * ___mail_sender prefers a Blade view at emails.{name} over the database
     * row. A view would make the template permanently uneditable in the admin
     * panel, which is the only lever available while
     * EmailTemplateController::create() is still a stub.
     */
    public function test_no_blade_view_shadows_a_database_template(): void
    {
        foreach (self::TEMPLATES as $name) {
            $this->assertFalse(
                view()->exists('emails.' . $name),
                "A Blade view exists for '{$name}' and would override the editable database row."
            );
        }
    }

    public function test_imported_parent_credentials_render_completely(): void
    {
        (new SendStudentSignupMail([[
            'email' => 'parent@example.test',
            'name' => 'Aisha Rahman',
            'password' => 'SchKp7Q@1',
            'school_code' => 'RIV8842',
            'school_name' => 'Riverside Primary',
        ]]))->handle();

        $this->assertFullyRendered('signup_school_user');

        $body = $this->lastMessage()['body'];
        $this->assertStringContainsString('SchKp7Q@1', $body, 'The password must reach the parent - it is their only way in.');
        $this->assertStringContainsString('Riverside Primary', $body);
    }

    /**
     * The delivered copy read "associated with the email  has been delete":
     * a declared token the caller never supplied, resolved to ''. No {token}
     * survives that, so assertFullyRendered alone cannot catch it - this
     * asserts the values are actually present.
     */
    public function test_account_deletion_notice_names_the_account(): void
    {
        $user = User::factory()->parent()->create([
            'name' => 'Priya Menon',
            'email' => 'priya.menon@example.test',
            'language' => 'english',
        ]);

        $this->actingAs($user, 'api')->postJson('/api/delete-user')->assertOk();

        $this->assertFullyRendered('delete_account');

        $body = $this->lastMessage()['body'];
        $this->assertStringContainsString('priya.menon@example.test', $body, 'The notice must name the address it deleted.');
        $this->assertStringContainsString('Priya Menon', $body);
        $this->assertStringContainsString((string) date('Y'), $body, 'The footer year must render, not collapse to blank.');
        $this->assertStringNotContainsString('has been delete ', $body);
    }

    public function test_off_roster_notice_renders_completely(): void
    {
        $school = School::factory()->create(['email' => 'office@school.test']);

        app(SchoolNotifier::class)->offRosterAttempt($school, 'stranger@example.test');

        $this->assertFullyRendered('school_offroster_attempt');
        $this->assertStringContainsString('stranger@example.test', $this->lastMessage()['body']);
    }

    public function test_seat_threshold_notice_renders_completely(): void
    {
        $school = School::factory()->withChildSeats(200)->create(['email' => 'office@school.test']);

        app(SchoolNotifier::class)->seatThreshold($school, 180, 200);

        $this->assertFullyRendered('school_seat_threshold');
        $this->assertStringContainsString('90', $this->lastMessage()['subject']);
    }

    public function test_import_summary_renders_completely(): void
    {
        $school = School::factory()->create(['email' => 'office@school.test']);

        app(SchoolNotifier::class)->importSummary($school, 184, 12, 4, 16);

        $this->assertFullyRendered('school_import_summary');
    }

    public function test_parent_invitation_renders_completely(): void
    {
        $school = School::factory()->create();
        $invite = SchoolParentInvite::factory()->for($school)->create();

        app(SchoolNotifier::class)->parentInvited($school, $invite);

        $this->assertFullyRendered('school_parent_invited');
    }

    /**
     * The school's code is circulating, so attempts arrive in bursts. One notice
     * an hour, or the send - which runs inline on the register request - becomes
     * both a mailbomb and a latency problem.
     */
    public function test_the_off_roster_notice_is_throttled_to_one_per_school(): void
    {
        $school = School::factory()->create(['email' => 'office@school.test']);
        $notifier = app(SchoolNotifier::class);

        $notifier->offRosterAttempt($school, 'one@example.test');
        $notifier->offRosterAttempt($school, 'two@example.test');
        $notifier->offRosterAttempt($school, 'three@example.test');

        $this->assertCount(1, collect($this->transport()->messages()), 'Only the first attempt in the window may send.');
    }

    public function test_a_school_with_no_contact_email_is_skipped_silently(): void
    {
        $school = School::factory()->create(['email' => null]);

        app(SchoolNotifier::class)->offRosterAttempt($school, 'stranger@example.test');

        $this->assertCount(0, collect($this->transport()->messages()));
    }
}
