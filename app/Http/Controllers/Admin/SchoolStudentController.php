<?php

namespace App\Http\Controllers\Admin;

use App\Mail\SchoolStudentCredentials;
use App\Http\Controllers\Controller;
use App\Models\BatteryEvent;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\User;
use App\Services\School\CredentialsWorkbook;
use App\Services\School\SchoolContractService;
use App\Services\School\SchoolSeatService;
use App\Services\School\StudentImportReader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SchoolStudentController extends Controller
{
    private const HEADER = StudentImportReader::HEADER;

    /**
     * How long a student's password-reset link stays valid. The school may import
     * a few days before the students first log in (half-term, weekends), so the
     * old 24 hours left dead links in the spreadsheet.
     */
    public const RESET_LINK_DAYS = 7;

    private function authorizeSchool(): void
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);
        abort_unless($permission && $permission->is_modify === 'yes', 403);
    }

    private function workbook(array $rows): string
    {
        return CredentialsWorkbook::build($rows);
    }

    public function sample()
    {
        $this->authorizeSchool();
        return response($this->workbook([self::HEADER, ['Student Name', 'student@example.com', 'student_school', '2013-05']]))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="student-import-template.xlsx"');
    }

    public function import(Request $request, $id)
    {
        $this->authorizeSchool();
        $request->validate(['students_excel' => 'required|file|mimes:xlsx,xls|max:5120']);
        $school = School::findOrFail($id);
        $book = IOFactory::load($request->file('students_excel')->getRealPath());
        try {
            $rows = (new StudentImportReader())->read($book->getActiveSheet());
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['students_excel' => $e->getMessage()]);
        } finally {
            $book->disconnectWorksheets();
        }
        $valid = [];
        $names = [];
        $emails = [];
        foreach ($rows as $index => $row) {
            if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
            [$name, $email, $username, $dob] = array_map(fn ($value) => trim((string) $value), $row);
            $email = strtolower($email);
            $error = null;
            try {
                $birth = Carbon::createFromFormat('!Y-m', $dob);
                $dateValid = $birth && $birth->format('Y-m') === $dob && $birth->lessThanOrEqualTo(now()) && $birth->age < 18;
            } catch (\Throwable $e) {
                $dateValid = false;
            }
            if (mb_strlen($name) < 3 || mb_strlen($name) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                || strlen($email) > 255 || !preg_match('/^[a-zA-Z0-9_.-]{3,100}$/', $username) || !$dateValid) {
                $error = 'Invalid name, email, username, or date of birth (student must be under 18).';
            }
            if (isset($names[strtolower($username)]) || isset($emails[$email])) $error = 'Duplicate email or username in the file.';
            if (User::withTrashed()->where('username', $username)->exists()
                || User::withTrashed()->where('user_role_id', 4)->where('email', $email)->exists()) $error = 'Student email or username already exists.';
            if ($error) throw ValidationException::withMessages(['students_excel' => 'Row '.($index + 2).': '.$error.' No accounts were created.']);
            $names[strtolower($username)] = true;
            $emails[$email] = true;
            $valid[] = compact('name', 'email', 'username', 'dob');
        }
        if (!$valid) throw ValidationException::withMessages(['students_excel' => 'No student rows found.']);

        $credentials = DB::transaction(function () use ($school, $valid) {
            $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if ($school->account_mode !== School::MODE_CHILD || $school->status !== 'active') {
                throw ValidationException::withMessages(['students_excel' => 'Student import requires an active school in Independent Child Accounts mode.']);
            }
            $remaining = app(SchoolSeatService::class)->remaining($school);
            if ($remaining !== null && count($valid) > $remaining) {
                throw ValidationException::withMessages(['students_excel' => 'Not enough child places. No accounts were created.']);
            }
            $output = [['Name', 'Email', 'Username', 'Temporary Password', 'Password Reset Link']];
            foreach ($valid as $student) {
                if (User::withTrashed()->where('username', $student['username'])->exists()
                    || User::withTrashed()->where('user_role_id', 4)->where('email', $student['email'])->exists()) {
                    throw ValidationException::withMessages(['students_excel' => 'Student email or username already exists. No accounts were created.']);
                }
                $password = $this->temporaryPassword();
                $token = Str::random(64);
                $user = User::create($student + [
                    'school_id' => $school->id, 'parent_id' => null, 'user_role_id' => 4, 'user_type' => 'child',
                    'password' => $password, 'status' => 'active', 'is_first_login' => 'yes',
                    'battery_points' => 100, 'is_avatar_primary' => 'no',
                    'password_reset_code' => $token, 'password_reset_expires_at' => now()->addDays(self::RESET_LINK_DAYS),
                ]);
                BatteryEvent::create(['user_id' => $user->id, 'direction' => 'credit', 'points' => 100,
                    'reason' => 'Initial default battery points', 'effective_date' => now()]);
                app(SchoolContractService::class)->grantParentEntitlement($school, $user);
                $output[] = [$student['name'], $student['email'], $student['username'], $password,
                    route('reset.password.page', ['token' => $token])];
            }
            return $output;
        });
        $file = (string) Str::uuid();
        $content = $this->workbook($credentials);
        Storage::disk('local')->put("student-credentials/{$school->id}/{$file}.xlsx", $content);
        $message = count($valid).' independent student account(s) created. Credentials emailed to the school.';
        try {
            // Mail::to() bypasses ___mail_sender, so the SMTP details an admin
            // saved under Settings would be ignored without this.
            ___apply_admin_mail_settings();
            Mail::to($school->email)->send(new SchoolStudentCredentials($school->name, $content, count($valid), self::RESET_LINK_DAYS));
        } catch (\Throwable $e) {
            Log::warning('Student credential email failed', ['school_id' => $school->id, 'error' => $e->getMessage()]);
            $message = count($valid).' independent student account(s) created. Email delivery failed; download the credentials below.';
        }
        return back()->with('success', $message)->with('student_credentials_file', $file);
    }

    public function credentials($id, $file)
    {
        $this->authorizeSchool();
        School::findOrFail($id);
        abort_unless(Storage::disk('local')->exists("student-credentials/{$id}/{$file}.xlsx"), 404);
        return Storage::disk('local')->download("student-credentials/{$id}/{$file}.xlsx", 'student-credentials.xlsx');
    }

    /**
     * 11 characters with upper, lower, digit and symbol so it satisfies the
     * reset-page policy; ~36 bits of randomness instead of the old ~18.
     */
    private function temporaryPassword(): string
    {
        return 'Stu@'.Str::random(6).random_int(1, 9);
    }

    /** Newest stored credentials file for a school (uuid), or null. */
    public static function latestCredentialsFile(int|string $schoolId): ?string
    {
        $disk = Storage::disk('local');
        $newest = null;
        $newestTime = 0;
        foreach ($disk->files("student-credentials/{$schoolId}") as $path) {
            if (!str_ends_with($path, '.xlsx')) {
                continue;
            }
            $time = $disk->lastModified($path);
            if ($time >= $newestTime) {
                $newestTime = $time;
                $newest = basename($path, '.xlsx');
            }
        }

        return $newest;
    }

    public function latestCredentials($id)
    {
        $this->authorizeSchool();
        School::findOrFail($id);
        $file = self::latestCredentialsFile($id);
        abort_unless($file, 404);

        return Storage::disk('local')->download("student-credentials/{$id}/{$file}.xlsx", 'student-credentials.xlsx');
    }
}
