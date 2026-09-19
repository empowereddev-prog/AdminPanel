<?php

namespace Tests\Feature\School;

use App\Jobs\SendStudentSignupMail;
use App\Models\PermissionUser;
use App\Models\School;
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
 * Two failures this pins, both found in the same investigation:
 *
 * 1. The whole import went to ONE job doing sequential SMTP. The worker runs
 *    --timeout=60 against a retry_after of 90, so a large import outlived the
 *    timeout, got re-reserved, and mailed the first parents their password a
 *    second time. Chunking is the fix, so the chunking is what is asserted.
 *
 * 2. The admin was told "run php artisan queue:work" - a shell command on a
 *    screen belonging to someone with no shell, and only there because nothing
 *    guaranteed the queue was drained. No admin-facing string may carry a CLI
 *    instruction again.
 */
class CredentialMailDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        (new SchoolEmailTemplateSeeder())->run();
        Mail::fake();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);

        return $admin;
    }

    private function parentSheet(int $rows): UploadedFile
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();

        $data = [['Name', 'Email', 'Country Code', 'Phone Number']];
        for ($i = 0; $i < $rows; $i++) {
            $data[] = ['Parent ' . $i, 'bulk' . $i . '.' . uniqid() . '@example.test', '+65', '8111' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)];
        }

        foreach ($data as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'import.xlsx', null, null, true);
    }

    public function test_a_large_import_is_split_across_several_jobs(): void
    {
        Queue::fake();

        $school = School::factory()->create(['max_limit' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet(45),
            ])
            ->assertRedirect();

        // 45 recipients at CHUNK=20 is three jobs. One job would be the bug.
        Queue::assertPushed(SendStudentSignupMail::class, 3);
    }

    public function test_no_chunk_exceeds_the_configured_size(): void
    {
        Queue::fake();

        $school = School::factory()->create(['max_limit' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet(45),
            ])
            ->assertRedirect();

        Queue::assertPushed(SendStudentSignupMail::class, function ($job) {
            $recipients = (new \ReflectionProperty($job, 'studentsToMail'));
            $recipients->setAccessible(true);
            $list = $recipients->getValue($job);

            $this->assertLessThanOrEqual(SendStudentSignupMail::CHUNK, count($list));
            $this->assertNotEmpty($list, 'An empty chunk would queue a job that mails nobody.');

            foreach ($list as $row) {
                // The token contract: a key missing here renders blank in the
                // template rather than failing, so the parent gets a password
                // email with no password in it.
                foreach (['email', 'name', 'password', 'school_code', 'school_name'] as $key) {
                    $this->assertArrayHasKey($key, $row);
                    $this->assertNotSame('', (string) $row[$key], "'{$key}' is empty in a queued chunk.");
                }
            }

            return true;
        });
    }

    public function test_a_small_import_still_queues_exactly_one_job(): void
    {
        Queue::fake();

        $school = School::factory()->create(['max_limit' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet(3),
            ])
            ->assertRedirect();

        Queue::assertPushed(SendStudentSignupMail::class, 1);
    }

    /**
     * The regression that started this: an admin screen telling its reader to
     * open a terminal.
     */
    public function test_the_import_message_carries_no_shell_instructions(): void
    {
        $school = School::factory()->create(['max_limit' => null]);

        $response = $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet(2),
            ]);

        $message = (string) (session('success') ?? session('error') ?? '');

        $this->assertNotSame('', $message, 'The import produced no message at all.');
        $this->assertDoesNotMatchRegularExpression(
            '/artisan|queue:work|db:seed|supervisor|storage\/logs/i',
            $message,
            "An admin-facing message must not contain operator instructions. Got: {$message}"
        );
        $this->assertStringContainsString('on their way', $message);
    }
}
