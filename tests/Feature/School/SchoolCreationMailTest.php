<?php

namespace Tests\Feature\School;

use App\Models\EmailTemplate;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Exercises the real school-creation path end to end:
 *
 *  - the school itself is emailed its code, plan and limits on creation
 *    (schools.email was collected and then never used by anything);
 *  - every imported parent is emailed their generated password;
 *  - the parent limit is optional, and a blank one means unlimited rather than
 *    "limit already reached".
 */
class SchoolCreationMailTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        (new SchoolEmailTemplateSeeder())->run();
        $this->transport()->flush();
    }

    private function transport()
    {
        return Mail::mailer()->getSymfonyTransport();
    }

    private function sentMessages(): array
    {
        return collect($this->transport()->messages())
            ->map(fn ($m) => [
                'to' => implode(',', array_map(fn ($a) => $a->getAddress(), $m->getOriginalMessage()->getTo())),
                'subject' => (string) $m->getOriginalMessage()->getSubject(),
                'body' => (string) ($m->getOriginalMessage()->getHtmlBody() ?? ''),
            ])->all();
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'user_role_id' => 1,
            'user_type' => 'admin',
            'status' => 'active',
        ]);

        PermissionUser::create([
            'user_id' => $admin->id,
            'menu_id' => 3,
            'is_view' => 'yes',
            'is_modify' => 'yes',
        ]);

        return $admin;
    }

    /** A parent spreadsheet in exactly the layout the importer requires. */
    private function parentSheet(array $rows): UploadedFile
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();

        $all = array_merge([['Name', 'Email', 'Country Code', 'Phone Number']], $rows);

        foreach ($all as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'parents') . '.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'parents.xlsx', null, null, true);
    }

    private function createSchool(array $overrides = [], ?UploadedFile $file = null)
    {
        $payload = array_merge([
            'school_name' => 'Riverside ' . uniqid(),
            'school_code' => strtoupper(substr(uniqid(), -8)),
            'email' => 'office' . uniqid() . '@school.test',
            'subscription_type' => 'monthly',
            'price' => '42.50',
            'child_seat_limit' => 50,
            'per_parent_child_limit' => 3,
        ], $overrides);

        if ($file) {
            // Laravel's post() takes files inside the data array, not as a
            // separate argument - passing them separately silently drops them.
            $payload['student_excel'] = $file;
        }

        return $this->actingAs($this->admin(), 'admin')->post('/store-school', $payload);
    }

    public function test_the_school_is_emailed_its_details_on_creation(): void
    {
        $response = $this->createSchool(['email' => 'office@riverside.test']);

        $response->assertRedirect('school');

        $onboarding = collect($this->sentMessages())->firstWhere('to', 'office@riverside.test');

        $this->assertNotNull($onboarding, 'The school received no onboarding email.');
        $this->assertStringContainsString('live', strtolower($onboarding['subject']));

        $school = School::latest('id')->first();
        $this->assertStringContainsString($school->school_code, $onboarding['body'], 'The school code must be in the email.');
        $this->assertStringContainsString('Monthly', $onboarding['body']);
        $this->assertStringNotContainsString('{', $onboarding['body'], 'Unsubstituted token left in the body.');

        $this->assertSame('42.50', number_format((float) $school->price, 2, '.', ''));
        $this->assertDatabaseHas('school_subscriptions', [
            'school_id' => $school->id,
            'price' => 42.50,
            'status' => 'successful',
        ]);
    }

    public function test_creating_a_school_without_a_price_is_allowed(): void
    {
        $response = $this->createSchool(['price' => null, 'email' => 'office@noprice.test']);

        $response->assertRedirect('school');
        $school = School::where('email', 'office@noprice.test')->first();
        $this->assertNotNull($school);
        $this->assertNull($school->price);
        $this->assertDatabaseHas('school_subscriptions', [
            'school_id' => $school->id,
            'price' => 0,
            'status' => 'successful',
        ]);
    }

    public function test_an_unlimited_parent_limit_is_shown_as_unlimited(): void
    {
        $this->createSchool(['email' => 'office@unlimited.test']);

        $body = collect($this->sentMessages())->firstWhere('to', 'office@unlimited.test')['body'];

        $this->assertStringContainsString('Unlimited', $body);
    }

    /** Item 4: the parent limit is optional and blank must not block an import. */
    public function test_a_school_can_be_created_without_a_parent_limit_and_still_import(): void
    {
        $file = $this->parentSheet([
            ['Aisha Rahman', 'aisha' . uniqid() . '@example.test', '+65', '81110001'],
            ['Ben Ortiz', 'ben' . uniqid() . '@example.test', '+65', '81110002'],
        ]);

        // max_limit deliberately absent.
        $response = $this->createSchool(['email' => 'office@nolimit.test'], $file);

        $response->assertRedirect('school');
        $response->assertSessionHas('success');

        $school = School::where('email', 'office@nolimit.test')->first();

        $this->assertNotNull($school);
        $this->assertNull($school->max_limit, 'A blank parent limit must store as null, not 0.');
        $this->assertSame(2, User::where('school_id', $school->id)->where('user_role_id', 3)->count());
        $this->assertSame(2, SchoolParentInvite::where('school_id', $school->id)->count());
    }

    /** Item 5: every imported parent must actually receive their password. */
    public function test_every_imported_parent_is_emailed_their_password(): void
    {
        $emailA = 'aisha' . uniqid() . '@example.test';
        $emailB = 'ben' . uniqid() . '@example.test';

        $this->createSchool(['email' => 'office@creds.test'], $this->parentSheet([
            ['Aisha Rahman', $emailA, '+65', '81110001'],
            ['Ben Ortiz', $emailB, '+65', '81110002'],
        ]));

        $messages = collect($this->sentMessages());

        foreach ([$emailA, $emailB] as $address) {
            $mail = $messages->firstWhere('to', $address);

            $this->assertNotNull($mail, "No credential email reached {$address}.");
            $this->assertStringContainsString('Sch', $mail['body'], 'The generated password is missing from the email.');
            $this->assertStringNotContainsString('{', $mail['body'], 'Unsubstituted token left in the body.');
        }
    }

    /** The credential mail must be queued, not sent inline, or a big import times out. */
    public function test_credential_mail_is_dispatched_to_the_queue(): void
    {
        Queue::fake();

        $this->createSchool(['email' => 'office@queued.test'], $this->parentSheet([
            ['Aisha Rahman', 'aisha' . uniqid() . '@example.test', '+65', '81110001'],
        ]));

        Queue::assertPushed(\App\Jobs\SendStudentSignupMail::class);
    }

    public function test_the_onboarding_template_is_seeded(): void
    {
        $this->assertTrue(
            EmailTemplate::where('variable_name', 'school_onboarded')->where('language', 'english')->exists()
        );
    }
}
