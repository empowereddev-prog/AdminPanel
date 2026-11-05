<?php

namespace Tests\Feature\School;

use App\Mail\SchoolStudentCredentials;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolSeatService;
use App\Services\School\StudentImportReader;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class IndependentStudentImportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $admin = User::factory()->create(['user_role_id' => 1, 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => 'yes']);
        $this->actingAs($admin, 'admin');
    }

    /** A DateTime value is written as a real Excel date cell in US display format. */
    private function sheet(array $rows, array $header = StudentImportReader::HEADER): UploadedFile
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        foreach (array_merge([$header], $rows) as $r => $row) {
            foreach ($row as $c => $value) {
                if ($value instanceof \DateTimeInterface) {
                    $sheet->setCellValue([$c + 1, $r + 1], ExcelDate::PHPToExcel($value));
                    $sheet->getStyle([$c + 1, $r + 1])->getNumberFormat()->setFormatCode('m/d/yyyy');
                } else {
                    $sheet->setCellValueExplicit([$c + 1, $r + 1], $value, DataType::TYPE_STRING);
                }
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'students').'.xlsx';
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return new UploadedFile($path, 'students.xlsx', null, null, true);
    }

    private function row(): array
    {
        return ['Student Name', uniqid('s').'@example.test', '2013-05'];
    }

    private function student(array $row): User
    {
        return User::where('user_role_id', 4)->where('email', $row[1])->firstOrFail();
    }

    private function credentials(School $school): array
    {
        $path = "student-credentials/{$school->id}/".session('student_credentials_file').'.xlsx';
        return IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet()->toArray();
    }

    public function test_import_creates_independent_children_and_sends_school_credentials(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $response = $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])]);
        $response->assertSessionHasNoErrors()->assertSessionHas('student_credentials_file');
        $child = $this->student($row);
        $this->assertSame(Str::before($row[1], '@'), $child->username);
        $this->assertSame('2013-05', $child->dob);
        $this->assertNull($child->parent_id);
        $this->assertSame($school->id, (int) $child->school_id);
        $this->assertSame(4, (int) $child->user_role_id);
        $this->assertSame(1, app(SchoolSeatService::class)->childCountForSchool($school->id));
        Mail::assertSent(SchoolStudentCredentials::class, fn ($mail) => $mail->hasTo($school->email));
        $file = session('student_credentials_file');
        $path = "student-credentials/{$school->id}/{$file}.xlsx";
        Storage::disk('local')->assertExists($path);
        $credentials = IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet()->toArray();
        // 11 characters, satisfying the reset-page policy (upper, lower, digit, symbol).
        $this->assertSame(11, strlen($credentials[1][3]));
        $this->assertMatchesRegularExpression('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,15}$/', $credentials[1][3]);
        // The reset link now lasts seven days so a weekend/half-term import still works on Monday.
        $this->assertTrue(\Carbon\Carbon::parse($child->fresh()->password_reset_expires_at)->greaterThan(now()->addDays(6)));
        $this->assertTrue(Hash::check($credentials[1][3], $child->password));
        $this->assertStringContainsString($child->password_reset_code, $credentials[1][4]);
        $this->get(route('school.students.credentials', [$school->id, $file]))->assertOk();
        $this->getJson(route('school.children.data', $school->id))->assertOk()
            ->assertJsonPath('data.0.account_relationship', 'Independent');
    }

    public function test_import_creates_a_class_of_two_hundred_students(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'child_seat_limit' => null]);
        // A counter, not uniqid(): uniqid can repeat inside a tight loop.
        $prefix = uniqid();
        $rows = array_map(fn ($n) => ["Student $n", "{$prefix}_{$n}@example.test", '2013-05'], range(1, 200));
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet($rows)])
            ->assertSessionHasNoErrors()->assertSessionHas('student_credentials_file');
        $children = User::where('school_id', $school->id)->where('user_role_id', 4)->whereNull('parent_id')->get();
        $this->assertCount(200, $children);
        $this->assertSame(200, \App\Models\Subscription::whereIn('user_id', $children->pluck('id'))->where('source', 'school_grant')->count());
        Mail::assertSent(SchoolStudentCredentials::class, 1);
        $credentials = $this->credentials($school);
        $this->assertCount(201, $credentials);
        // Passwords are hashed before the transaction; each one must still match its own row.
        foreach ([1, 100, 200] as $line) {
            $child = $children->firstWhere('username', $credentials[$line][2]);
            $this->assertTrue(Hash::check($credentials[$line][3], $child->password));
        }
    }

    public function test_linked_school_rejects_independent_import(): void
    {
        $school = School::factory()->create();
        $row = $this->row();
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasErrors('students_excel');
        $this->assertDatabaseMissing('users', ['email' => $row[1]]);
        Mail::assertNothingSent();
    }

    public function test_invalid_row_rolls_back_entire_upload(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $bad = $this->row();
        $bad[2] = '1990-01';
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row, $bad])])
            ->assertSessionHasErrors(['students_excel' => 'Row 3: Student must be under 18. No accounts were created.']);
        $this->assertDatabaseMissing('users', ['email' => $row[1]]);
        Mail::assertNothingSent();
    }

    public function test_username_is_the_email_prefix_with_a_suffix_on_clashes(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $p = uniqid('u');
        User::factory()->create(['username' => "$p.tan"]);
        // The unique index still covers deleted users, so their usernames stay taken.
        User::factory()->create(['username' => "$p.x"])->delete();
        $rows = [
            ['First Student', "$p.tan@a.test", '2013-05'],
            ['Second Student', "$p.tan@b.test", '2013-05'],
            ['Third Student', "$p+x@c.test", '2013-05'],
            ['Fourth Student', "$p.new@d.test", '2013-05'],
        ];
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet($rows)])
            ->assertSessionHasNoErrors();
        $expected = ["$p.tan_2", "$p.tan_3", "$p.x_2", "$p.new"];
        $this->assertSame($expected, array_map(fn ($row) => $this->student($row)->username, $rows));
        $this->assertSame($expected, array_column(array_slice($this->credentials($school), 1), 2));
    }

    public function test_students_may_share_a_name(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $rows = [$this->row(), $this->row()];
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet($rows)])
            ->assertSessionHasNoErrors();
        $this->assertSame(['Student Name', 'Student Name'], array_map(fn ($row) => $this->student($row)->name, $rows));
    }

    public function test_an_email_repeated_in_the_file_rejects_the_upload(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $copy = ['Other Name', strtoupper($row[1]), '2012-01'];
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row, $copy])])
            ->assertSessionHasErrors(['students_excel' => 'Row 3: Duplicate email in the file. No accounts were created.']);
        $this->assertDatabaseMissing('users', ['email' => $row[1]]);
    }

    public function test_an_active_students_email_is_rejected_but_a_deleted_ones_is_not(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $existing = User::factory()->create(['user_role_id' => 4, 'email' => $row[1], 'school_id' => $school->id]);
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasErrors(['students_excel' => 'Row 2: Student email already exists. No accounts were created.']);
        $existing->delete();
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasNoErrors();
        $this->assertNotSame($existing->id, $this->student($row)->id);
    }

    public function test_the_old_template_still_imports_and_its_username_is_ignored(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $legacy = ['Name', 'Email', 'Username', 'Date of Birth (YYYY-MM)'];
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([[$row[0], $row[1], 'typed_by_school', $row[2]]], $legacy)])
            ->assertSessionHasNoErrors();
        $this->assertSame(Str::before($row[1], '@'), $this->student($row)->username);
    }

    public function test_dates_of_birth_in_common_formats_are_stored_as_year_and_month(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $cell = ['Date Cell', uniqid('d').'@example.test', new \DateTime('2013-04-05')];
        $typed = ['Typed Date', uniqid('t').'@example.test', '14/05/2013'];
        $named = ['Month Name', uniqid('m').'@example.test', 'Sep 2012'];
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$cell, $typed, $named])])
            ->assertSessionHasNoErrors();
        $this->assertSame(['2013-04', '2013-05', '2012-09'], array_map(fn ($row) => $this->student($row)->dob, [$cell, $typed, $named]));
    }

    public function test_an_unrecognised_date_of_birth_names_the_row(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $row[2] = 'sometime in 2013';
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasErrors(['students_excel' => 'Row 2: Date of birth not recognised (e.g. 2013-05 or 14/05/2013). No accounts were created.']);
    }

    public function test_children_remain_visible_after_switching_school_mode(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $independent = User::factory()->create(['user_role_id' => 4, 'school_id' => $school->id, 'parent_id' => null]);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $linked = User::factory()->create(['user_role_id' => 4, 'parent_id' => $parent->id, 'school_id' => null]);
        $school->update(['account_mode' => 'linked']);
        $data = $this->getJson(route('school.children.data', $school->id))->assertOk()->json('data');
        $this->assertCount(2, $data);
        $this->assertEqualsCanonicalizing(['Independent', 'Parent-linked'], array_column($data, 'account_relationship'));
        $this->assertSame(2, app(SchoolSeatService::class)->childCountForSchool($school->id));
    }

    public function test_view_offers_student_import_only_in_independent_mode(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $this->get(route('school.show', $school->id))->assertOk()->assertSee('Upload Students')->assertDontSee('Import parents');
        $school->update(['account_mode' => 'linked']);
        $this->get(route('school.show', $school->id))->assertOk()->assertDontSee('Upload Students')->assertSee('Import parents');
    }

    public function test_student_limit_counts_existing_linked_children(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'child_seat_limit' => 1]);
        $parent = User::factory()->parent()->inSchool($school)->create();
        User::factory()->create(['user_role_id' => 4, 'parent_id' => $parent->id, 'school_id' => null]);
        $row = $this->row();
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasErrors('students_excel');
        $this->assertDatabaseMissing('users', ['email' => $row[1]]);
        Mail::assertNothingSent();
    }

    public function test_credentials_download_requires_school_modify_permission(): void
    {
        $school = School::factory()->create();
        $file = (string) \Illuminate\Support\Str::uuid();
        $this->get(route('school.students.credentials', [$school->id, $file]))->assertNotFound();
        Storage::disk('local')->put("student-credentials/{$school->id}/{$file}.xlsx", 'private credentials');
        $user = User::factory()->create(['user_role_id' => 2, 'status' => 'active']);
        $this->actingAs($user, 'admin')->get(route('school.students.credentials', [$school->id, $file]))
            ->assertForbidden();
    }
}
