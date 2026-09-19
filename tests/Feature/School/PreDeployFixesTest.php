<?php

namespace Tests\Feature\School;

use App\Jobs\NotifyVideoContentAudience;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Services\School\SchoolContractService;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * The blocker fixes cleared before this branch ships. Each test pins one
 * failure that was reachable on day one.
 */
class PreDeployFixesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        (new SchoolEmailTemplateSeeder())->run();
        Cache::flush();
        // The array transport (phpunit.xml MAIL_MAILER=array) rather than
        // Mail::fake(): these tests need to read what was actually rendered.
        $this->transport()->flush();
    }

    private function transport()
    {
        return Mail::mailer()->getSymfonyTransport();
    }

    private function sentSubjects(): array
    {
        return collect($this->transport()->messages())
            ->map(fn ($m) => (string) $m->getOriginalMessage()->getSubject())
            ->all();
    }

    private function admin(int $menuId = 3): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => $menuId, 'is_view' => 'yes', 'is_modify' => 'yes']);

        return $admin;
    }

    private function addChild(array $overrides = [])
    {
        return $this->postJson('/api/add-child', array_merge([
            'name' => 'Small Child',
            'dob' => '2019-04',
            'username' => 'child' . uniqid(),
            'password' => 'ChildPass1@',
            'language' => 'english',
        ], $overrides));
    }

    /**
     * A1 - one notification per person, however many devices they own.
     *
     * Asserted on the recipient list rather than on AppNotification rows:
     * sendNotificationSender() builds its Firebase client before it writes
     * the in-app row, so with no credentials present it produces nothing at
     * all and the side effect cannot be observed here.
     */
    public function test_a_multi_device_user_is_notified_once(): void
    {
        $user = User::factory()->parent()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'phone-token']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tablet-token']);

        $job = new NotifyVideoContentAudience(
            [$user->id, $user->id],
            'New podcast',
            'Have a listen',
            'video_content',
            ['id' => 1]
        );

        $this->assertSame(
            [$user->id],
            $job->recipients(),
            'Two device tokens and a duplicated id must collapse to one recipient.'
        );
    }

    /** A1 - the audience is the user list; a device row is not a prerequisite. */
    public function test_recipients_do_not_depend_on_device_tokens(): void
    {
        $withDevice = User::factory()->parent()->create();
        $withoutDevice = User::factory()->parent()->create();
        DeviceToken::create(['user_id' => $withDevice->id, 'token' => 'only-token']);

        $job = new NotifyVideoContentAudience(
            [$withDevice->id, $withoutDevice->id, 0, null],
            'T',
            'B',
            'video_content',
            ['id' => 2]
        );

        $this->assertSame([$withDevice->id, $withoutDevice->id], $job->recipients());
    }

    /** A1 - the job no longer reads device_tokens at all. */
    public function test_the_job_does_not_query_device_tokens(): void
    {
        $source = file_get_contents(base_path('app/Jobs/NotifyVideoContentAudience.php'));

        // The class name still appears in the comment explaining the fix;
        // what must be gone is the query.
        $this->assertStringNotContainsString('DeviceToken::', $source);
        $this->assertStringNotContainsString('use App\\Models\\DeviceToken;', $source);
    }

    /** A2 - crossing 90% of the child cap warns the school, once. */
    public function test_crossing_ninety_percent_warns_the_school_once(): void
    {
        $school = School::factory()->withChildSeats(10)->create(['email' => 'office@school.test']);
        $parent = User::factory()->parent()->inSchool($school)->create();

        // Eight children already: the next one puts the school on 9/10 = 90%.
        User::factory()->count(8)->child($parent)->create();

        Passport::actingAs($parent, [], 'api');
        $this->addChild()->assertStatus(201)->assertJson(['status' => true]);

        $subjects = $this->sentSubjects();
        $seatNotices = array_filter($subjects, fn ($s) => str_contains($s, '90'));

        $this->assertCount(1, $seatNotices, 'Crossing 90% must warn the school exactly once. Sent: ' . implode(' | ', $subjects));
    }

    /**
     * A2 - and not again for the same threshold the same day.
     *
     * 20 seats, not 10: with 10 the second add would move the school from the
     * 90% band into the 100% one, which is a different threshold and a notice
     * the school genuinely should get. 18/20 then 19/20 both sit at 90%.
     */
    public function test_the_seat_warning_is_throttled_within_the_day(): void
    {
        $school = School::factory()->withChildSeats(20)->create(['email' => 'office@school.test']);
        $parent = User::factory()->parent()->inSchool($school)->create();
        User::factory()->count(17)->child($parent)->create();

        Passport::actingAs($parent, [], 'api');
        $this->addChild()->assertStatus(201);
        $afterFirst = count($this->sentSubjects());

        $this->addChild(['username' => 'child' . uniqid()])->assertStatus(201);

        $this->assertCount(
            $afterFirst,
            $this->sentSubjects(),
            'The 24h throttle must stop a second notice for the same threshold.'
        );
    }

    /** A2 - but reaching 100% is a different threshold and does warn again. */
    public function test_reaching_the_cap_warns_again(): void
    {
        $school = School::factory()->withChildSeats(10)->create(['email' => 'office@school.test']);
        $parent = User::factory()->parent()->inSchool($school)->create();
        User::factory()->count(8)->child($parent)->create();

        Passport::actingAs($parent, [], 'api');
        $this->addChild()->assertStatus(201);            // 9/10 - 90%
        $afterNinety = count($this->sentSubjects());

        $this->addChild(['username' => 'child' . uniqid()])->assertStatus(201); // 10/10 - 100%

        $this->assertGreaterThan(
            $afterNinety,
            count($this->sentSubjects()),
            'Hitting the cap is a separate threshold and must be reported.'
        );
    }

    /** A2 - a school with no cap configured is never mailed about seats. */
    public function test_a_school_without_a_child_cap_is_not_warned(): void
    {
        $school = School::factory()->create(['email' => 'office@school.test']);
        $parent = User::factory()->parent()->inSchool($school)->create();

        Passport::actingAs($parent, [], 'api');
        $this->addChild()->assertStatus(201);

        $this->assertSame([], array_values(array_filter(
            $this->sentSubjects(),
            fn ($s) => str_contains(strtolower($s), 'seat') || str_contains($s, '90')
        )));
    }

    /** A3 - a multipart ETag is not an MD5, so it must never report a match. */
    public function test_a_multipart_etag_is_not_treated_as_a_content_hash(): void
    {
        $source = file_get_contents(base_path('app/helper.php'));
        $fn = substr($source, strpos($source, 'function uploadFileMatchesExisting'));
        $fn = substr($fn, 0, strpos($fn, "\nfunction s3ObjectEtag"));

        $this->assertStringContainsString("str_contains(\$remoteEtag, '-')", $fn);
        $this->assertStringContainsString('md5_file($localPath)', $fn);
    }

    /** A3 - off S3 (the faked disk) the stream fallback still answers. */
    public function test_etag_lookup_returns_null_without_an_s3_client(): void
    {
        \Illuminate\Support\Facades\Storage::fake('s3');
        \Illuminate\Support\Facades\Storage::disk('s3')->put('assets/video/x.mp4', 'bytes');

        $this->assertNull(s3ObjectEtag('assets/video/x.mp4'));
    }

    /** A4 - a rejected spreadsheet must not leave the school behind. */
    public function test_a_bad_header_leaves_no_school_row(): void
    {
        $name = 'Header Reject School ' . uniqid();
        $code = 'HRS' . random_int(1000, 9999);

        $ss = new Spreadsheet();
        foreach ([['Wrong', 'Header', 'Row', 'Here'], ['A', 'a@x.test', '+65', '91234567']] as $r => $row) {
            foreach ($row as $c => $value) {
                $ss->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($ss))->save($path);

        $payload = [
            'school_name' => $name,
            'school_code' => $code,
            'subscription_type' => 'monthly',
            'email' => 'head' . random_int(1000, 9999) . '@example.test',
            'student_excel' => new UploadedFile($path, 'import.xlsx', null, null, true),
        ];

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.store'), $payload)
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('schools', ['name' => $name]);
        $this->assertDatabaseMissing('schools', ['school_code' => $code]);
    }

    /** A6 - a real purchase stays on the report even once the buyer joins a school. */
    public function test_a_paid_purchase_by_a_school_parent_is_still_listed(): void
    {
        $school = School::factory()->create(['name' => 'Paid Parent School ' . uniqid()]);
        $parent = User::factory()->parent()->inSchool($school)->create(['name' => 'Paying Parent ' . uniqid()]);

        Subscription::create([
            'user_id' => $parent->id,
            'subscription_type_id' => 'com.empowered.yearly',
            'user_type' => 'parent',
            'subscription_type' => 'yearly',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'currency' => 'SGD',
            'status' => 'successful',
            'price' => '101.63',
        ]);

        (new SchoolContractService())->grantParentEntitlement($school, User::factory()->parent()->inSchool($school)->create());

        $rows = collect($this->actingAs($this->admin(23), 'admin')
            ->get(route('payment.history_index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->json('data') ?? []);

        $this->assertContains($parent->name, $rows->pluck('user_name')->all(), 'A paid IAP row must survive the school filter.');
    }

    /** A7 - a lapsed personal plan must not block the school's entitlement. */
    public function test_an_expired_subscription_does_not_block_the_school_grant(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();

        Subscription::create([
            'user_id' => $parent->id,
            'subscription_type_id' => 'com.empowered.monthly',
            'user_type' => 'parent',
            'subscription_type' => 'monthly',
            'start_date' => now()->subMonths(8)->toDateString(),
            'end_date' => now()->subMonths(7)->toDateString(),
            'currency' => 'SGD',
            'status' => 'successful',
            'price' => '13.49',
        ]);

        (new SchoolContractService())->grantParentEntitlement($school, $parent);

        $this->assertSame(
            1,
            Subscription::where('user_id', $parent->id)->where('end_date', '>=', now()->toDateString())->count(),
            'The school grant should be created despite the expired row.'
        );
    }

    /** A7 - a live plan still suppresses a duplicate grant. */
    public function test_a_live_subscription_still_blocks_a_second_grant(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();

        (new SchoolContractService())->grantParentEntitlement($school, $parent);
        (new SchoolContractService())->grantParentEntitlement($school, $parent);

        $this->assertSame(1, Subscription::where('user_id', $parent->id)->count());
    }
}
