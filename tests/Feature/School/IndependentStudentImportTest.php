<?php

namespace Tests\Feature\School;

use App\Mail\SchoolStudentCredentials;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolSeatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    private function sheet(array $rows): UploadedFile
    {
        $book = new Spreadsheet();
        foreach (array_merge([['Name', 'Email', 'Username', 'Date of Birth (YYYY-MM)']], $rows) as $r => $row) {
            foreach ($row as $c => $value) $book->getActiveSheet()->setCellValueExplicit([$c + 1, $r + 1], $value, DataType::TYPE_STRING);
        }
        $path = tempnam(sys_get_temp_dir(), 'students').'.xlsx';
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return new UploadedFile($path, 'students.xlsx', null, null, true);
    }

    private function row(): array
    {
        $id = uniqid();
        return ['Student Name', $id.'@example.test', 'student_'.$id, '2013-05'];
    }

    public function test_import_creates_independent_children_and_sends_school_credentials(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $response = $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])]);
        $response->assertSessionHasNoErrors()->assertSessionHas('student_credentials_file');
        $child = User::where('username', $row[2])->firstOrFail();
        $this->assertNull($child->parent_id);
        $this->assertSame($school->id, (int) $child->school_id);
        $this->assertSame(4, (int) $child->user_role_id);
        $this->assertSame(1, app(SchoolSeatService::class)->childCountForSchool($school->id));
        Mail::assertSent(SchoolStudentCredentials::class, fn ($mail) => $mail->hasTo($school->email));
        $file = session('student_credentials_file');
        $path = "student-credentials/{$school->id}/{$file}.xlsx";
        Storage::disk('local')->assertExists($path);
        $credentials = IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet()->toArray();
        $this->assertTrue(Hash::check($credentials[1][3], $child->password));
        $this->assertStringContainsString($child->password_reset_code, $credentials[1][4]);
        $this->get(route('school.students.credentials', [$school->id, $file]))->assertOk();
        $this->getJson(route('school.children.data', $school->id))->assertOk()
            ->assertJsonPath('data.0.account_relationship', 'Independent');
    }

    public function test_linked_school_rejects_independent_import(): void
    {
        $school = School::factory()->create();
        $row = $this->row();
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row])])
            ->assertSessionHasErrors('students_excel');
        $this->assertDatabaseMissing('users', ['username' => $row[2]]);
        Mail::assertNothingSent();
    }

    public function test_invalid_row_rolls_back_entire_upload(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $row = $this->row();
        $bad = $this->row();
        $bad[3] = '1990-01';
        $this->post(route('school.import.students', $school->id), ['students_excel' => $this->sheet([$row, $bad])])
            ->assertSessionHasErrors('students_excel');
        $this->assertDatabaseMissing('users', ['username' => $row[2]]);
        Mail::assertNothingSent();
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
        $this->assertDatabaseMissing('users', ['username' => $row[2]]);
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
