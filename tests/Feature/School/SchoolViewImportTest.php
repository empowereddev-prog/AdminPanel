<?php

namespace Tests\Feature\School;

use App\Models\PermissionUser;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Database\Seeders\SchoolEmailTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * The parent and teacher uploads offered from the school view. Both run through
 * SchoolImportService, so these also cover the extraction of that logic out of
 * SchoolController::update.
 */
class SchoolViewImportTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        (new SchoolEmailTemplateSeeder())->run();
        Mail::fake();
    }

    private function admin(string $isModify = 'yes'): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => 3, 'is_view' => 'yes', 'is_modify' => $isModify]);

        return $admin;
    }

    private function sheet(array $header, array $rows): UploadedFile
    {
        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();

        foreach (array_merge([$header], $rows) as $r => $row) {
            foreach ($row as $c => $value) {
                $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($ss))->save($path);

        return new UploadedFile($path, 'import.xlsx', null, null, true);
    }

    private function parentSheet(array $rows): UploadedFile
    {
        return $this->sheet(['Name', 'Email', 'Country Code', 'Phone Number'], $rows);
    }

    private function staffSheet(array $rows): UploadedFile
    {
        return $this->sheet(['Name', 'Email', 'Country Code', 'Phone Number', 'Username'], $rows);
    }

    public function test_parents_can_be_imported_from_the_school_view(): void
    {
        $school = School::factory()->create();
        $email = 'p' . uniqid() . '@example.test';

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet([['Aisha Rahman', $email, '+65', '81110001']]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => $email, 'school_id' => $school->id, 'user_role_id' => 3]);
        $this->assertDatabaseHas('school_parent_invites', [
            'school_id' => $school->id,
            'email' => SchoolParentInvite::normaliseEmail($email),
            'status' => 'claimed',
        ]);
    }

    public function test_teachers_can_be_imported_from_the_school_view(): void
    {
        $school = School::factory()->create();
        $email = 't' . uniqid() . '@example.test';
        $username = 'tch' . substr(uniqid(), -6);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.staff', $school->id, false), [
                'staff_excel' => $this->staffSheet([['Daniel Tan', $email, '+65', '81110009', $username]]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'school_id' => $school->id,
            'user_role_id' => 5,
            'username' => $username,
        ]);
    }

    /** Teachers must not appear on the parent roster or eat parent places. */
    public function test_a_teacher_import_creates_no_roster_entry(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.staff', $school->id, false), [
                'staff_excel' => $this->staffSheet([
                    ['Daniel Tan', 't' . uniqid() . '@example.test', '+65', '81110009', 'tch' . substr(uniqid(), -6)],
                ]),
            ]);

        $this->assertSame(0, SchoolParentInvite::where('school_id', $school->id)->count());
    }

    public function test_a_wrong_header_is_rejected_without_creating_anyone(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->sheet(['Nope', 'Wrong'], [['a', 'b']]),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, User::where('school_id', $school->id)->count());
    }

    /** A blank parent limit must not be read as "no places left". */
    public function test_an_unlimited_school_imports_normally(): void
    {
        $school = School::factory()->create(['max_limit' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet([
                    ['A One', 'a' . uniqid() . '@example.test', '+65', '81110001'],
                    ['B Two', 'b' . uniqid() . '@example.test', '+65', '81110002'],
                ]),
            ])
            ->assertSessionHas('success');

        $this->assertSame(2, User::where('school_id', $school->id)->where('user_role_id', 3)->count());
    }

    public function test_the_parent_limit_is_honoured_when_set(): void
    {
        $school = School::factory()->create(['max_limit' => 1]);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet([
                    ['A One', 'a' . uniqid() . '@example.test', '+65', '81110001'],
                    ['B Two', 'b' . uniqid() . '@example.test', '+65', '81110002'],
                ]),
            ]);

        $this->assertSame(1, User::where('school_id', $school->id)->where('user_role_id', 3)->count());
    }

    public function test_the_teacher_roster_lists_only_this_schools_teachers(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        User::factory()->count(2)->teacher()->inSchool($school)->create();
        User::factory()->teacher()->inSchool($other)->create();
        // Parents must not leak into the teacher roster.
        User::factory()->parent()->inSchool($school)->create();

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.teachers.data', $school->id, false))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data);

        foreach ($data as $row) {
            $this->assertArrayHasKey('username', $row);
            $this->assertArrayHasKey('status_badge', $row);
        }
    }

    public function test_an_imported_teacher_appears_in_the_teacher_roster(): void
    {
        $school = School::factory()->create();
        $username = 'tch' . substr(uniqid(), -6);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('school.import.staff', $school->id, false), [
                'staff_excel' => $this->staffSheet([
                    ['Daniel Tan', 't' . uniqid() . '@example.test', '+65', '81110009', $username],
                ]),
            ]);

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.teachers.data', $school->id, false))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($username, $data[0]['username']);
    }

    public function test_a_view_only_admin_cannot_read_the_teacher_roster(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin('no'), 'admin')
            ->getJson(route('school.teachers.data', $school->id, false))
            ->assertStatus(403);
    }

    /** Parents and the roster share one card; teachers stay on Teacher Roster. */
    public function test_the_school_user_list_shows_parents_and_not_teachers(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create(['name' => 'Parent Person']);
        $teacher = User::factory()->teacher()->inSchool($school)->create(['name' => 'Teacher Person']);

        $html = $this->actingAs($this->admin(), 'admin')
            ->get('/view-school-details/' . $school->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Parents<', $html);
        $this->assertStringNotContainsString('Parent Accounts', $html);
        $this->assertStringContainsString('Teacher Roster', $html);

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.roster.data', $school->id, false))
            ->assertOk()
            ->json('data');

        $emails = collect($data)->pluck('email')->all();
        $this->assertTrue(collect($emails)->contains(fn ($email) => str_contains(html_entity_decode($email), $parent->email)));
        $this->assertFalse(collect($emails)->contains(fn ($email) => str_contains((string) $email, $teacher->email)));
    }

    public function test_the_child_list_shows_children_of_this_schools_parents(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $parent = User::factory()->parent()->inSchool($school)->create(['name' => 'Aisha Rahman']);
        User::factory()->count(2)->child($parent)->create();

        // Another school's child, and a consumer parent's child, must not appear.
        User::factory()->child(User::factory()->parent()->inSchool($other)->create())->create();
        User::factory()->child(User::factory()->parent()->create())->create();

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.children.data', $school->id, false))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data);
        $this->assertStringContainsString('Aisha Rahman', $data[0]['parent']);
    }

    public function test_a_deleted_child_drops_off_the_list(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->parent()->inSchool($school)->create();
        $child = User::factory()->child($parent)->create();

        $child->delete();

        $data = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('school.children.data', $school->id, false))
            ->assertOk()
            ->json('data');

        $this->assertCount(0, $data, 'A soft-deleted child frees its place and must leave the list.');
    }

    public function test_a_view_only_admin_cannot_read_the_child_list(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin('no'), 'admin')
            ->getJson(route('school.children.data', $school->id, false))
            ->assertStatus(403);
    }

    /** Date-range parent export was unused and is gone from the school view. */
    public function test_parent_date_export_is_not_on_the_school_view(): void
    {
        $school = School::factory()->create();

        $html = $this->actingAs($this->admin(), 'admin')
            ->get('/view-school-details/' . $school->id)
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Export Parents', $html);
        $this->assertStringNotContainsString('name="start_date"', $html);

        $this->actingAs($this->admin(), 'admin')
            ->get('/school/' . $school->id . '/export-users?role_type=parent')
            ->assertNotFound();
    }

    /** The page had no flash region, so every redirect-with-message was invisible. */
    public function test_the_school_view_renders_flash_messages(): void
    {
        $school = School::factory()->create();

        $html = $this->actingAs($this->admin(), 'admin')
            ->withSession(['error' => 'No users found matching the selected criteria.'])
            ->get('/view-school-details/' . $school->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('No users found matching the selected criteria.', $html);
    }

    public function test_a_view_only_admin_cannot_import(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin('no'), 'admin')
            ->post(route('school.import.parents', $school->id, false), [
                'parent_excel' => $this->parentSheet([['A One', 'a' . uniqid() . '@example.test', '+65', '81110001']]),
            ])
            ->assertRedirect('dashboard');

        $this->assertSame(0, User::where('school_id', $school->id)->count());
    }
}
