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
use Illuminate\Support\Facades\Hash;
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
        return response($this->workbook([self::HEADER, ['Student Name', 'student@example.com', '2013-05']]))
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="student-import-template.xlsx"');
    }

    public function import(Request $request, $id)
    {
        $this->authorizeSchool();
        // Each student costs one bcrypt hash (~0.2s at 12 rounds), so a class-sized
        // upload of 200+ rows runs past PHP's default 30s limit.
        set_time_limit(300);
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
        $emails = [];
        foreach ($rows as $index => $row) {
            if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) continue;
            // Names may repeat; only the email has to be unique.
            [$name, $email, $dob] = array_map(fn ($value) => trim((string) $value), $row);
            $email = strtolower($email);
            $dob = StudentImportReader::dob($dob);
            $birth = $dob ? Carbon::createFromFormat('!Y-m', $dob) : null;
            $error = match (true) {
                mb_strlen($name) < 3 || mb_strlen($name) > 50 => 'Name must be 3–50 characters.',
                !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 => 'Invalid email.',
                !$birth => 'Date of birth not recognised (e.g. 2013-05 or 14/05/2013).',
                $birth->greaterThan(now()) => 'Date of birth is in the future.',
                $birth->age >= 18 => 'Student must be under 18.',
                isset($emails[$email]) => 'Duplicate email in the file.',
                $this->studentEmailTaken($email) => 'Student email already exists.',
                default => null,
            };
            if ($error) throw ValidationException::withMessages(['students_excel' => 'Row '.($index + 2).': '.$error.' No accounts were created.']);
            $emails[$email] = true;
            $valid[] = compact('name', 'email', 'dob');
        }
        if (!$valid) throw ValidationException::withMessages(['students_excel' => 'No student rows found.']);

        // Hash before the transaction so the school row is locked only for the
        // inserts, not for the whole hashing time.
        $passwords = array_map(fn () => $this->temporaryPassword(), $valid);
        $hashes = array_map(fn ($password) => Hash::make($password), $passwords);

        $credentials = DB::transaction(function () use ($school, $valid, $passwords, $hashes) {
            $school = School::whereKey($school->id)->lockForUpdate()->firstOrFail();
            if ($school->account_mode !== School::MODE_CHILD || $school->status !== 'active') {
                throw ValidationException::withMessages(['students_excel' => 'Student import requires an active school in Independent Child Accounts mode.']);
            }
            $remaining = app(SchoolSeatService::class)->remaining($school);
            if ($remaining !== null && count($valid) > $remaining) {
                throw ValidationException::withMessages(['students_excel' => 'Not enough child places. No accounts were created.']);
            }
            $output = [['Name', 'Email', 'Username', 'Temporary Password', 'Password Reset Link']];
            $taken = [];
            foreach ($valid as $i => $student) {
                if ($this->studentEmailTaken($student['email'])) {
                    throw ValidationException::withMessages(['students_excel' => 'Student email already exists. No accounts were created.']);
                }
                $username = $this->username($student['email'], $taken);
                $token = Str::random(64);
                $user = User::create($student + [
                    'username' => $username,
                    'school_id' => $school->id, 'parent_id' => null, 'user_role_id' => 4, 'user_type' => 'child',
                    'password' => $hashes[$i], 'status' => 'active', 'is_first_login' => 'yes',
                    'battery_points' => 100, 'is_avatar_primary' => 'no',
                    'password_reset_code' => $token, 'password_reset_expires_at' => now()->addDays(self::RESET_LINK_DAYS),
                ]);
                BatteryEvent::create(['user_id' => $user->id, 'direction' => 'credit', 'points' => 100,
                    'reason' => 'Initial default battery points', 'effective_date' => now()]);
                app(SchoolContractService::class)->grantParentEntitlement($school, $user);
                $output[] = [$student['name'], $student['email'], $username, $passwords[$i],
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

    /** Deleted students don't count, so a removed student can be imported again. */
    private function studentEmailTaken(string $email): bool
    {
        return User::where('user_role_id', 4)->where('email', $email)->exists();
    }

    /**
     * The student's login name: their email prefix (mary+test@x.sg → mary.test),
     * with _2, _3… on a clash. Deleted users count because the unique index does.
     */
    private function username(string $email, array &$taken): string
    {
        $base = preg_replace(['/[^a-z0-9_.-]+/', '/\.{2,}/'], '.', strtolower(Str::ascii(Str::before($email, '@'))));
        $base = substr(trim($base, '._-'), 0, 90);
        if ($base === '') $base = 'student';
        $username = $base;
        for ($n = 2; isset($taken[$username]) || User::withTrashed()->where('username', $username)->exists(); $n++) {
            $username = $base.'_'.$n;
        }
        $taken[$username] = true;
        return $username;
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
