<?php

namespace App\Services\School;

use App\Jobs\SendStudentSignupMail;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The spreadsheet imports, in one place.
 *
 * Both were previously inline in SchoolController::update, reachable only from
 * the Edit screen. Lifting them out is what lets the school view offer the same
 * upload without a second copy of the parsing, validation and mail logic.
 *
 * Returns a report rather than a redirect so the caller decides how to present
 * it - the two screens phrase their messages differently.
 */
class SchoolImportService
{
    private const PARENT_HEADER = ['Name', 'Email', 'Country Code', 'Phone Number'];
    private const STAFF_HEADER = ['Name', 'Email', 'Country Code', 'Phone Number', 'Username'];

    /**
     * @return array{status:bool,message:string,imported:int,skipped:int,rejected:int}
     */
    public function importParents(School $school, UploadedFile $file, ?int $maxLimit = null): array
    {
        $rows = $this->rows($school, $file, 'parents', self::PARENT_HEADER);

        if ($rows === null) {
            return $this->fail('Invalid file format. Please use the provided parent sample.');
        }

        $valid = [];
        $rejected = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!array_filter($row) || empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3])) {
                continue;
            }

            // Same field rules the Edit-screen import has always applied.
            if (preg_match('/^0/', $row[3]) || preg_match('/^0+$/', $row[3]) || !preg_match('/^\d{8,15}$/', $row[3])) {
                $rejected++;
                continue;
            }

            if (!preg_match('/^\+\d+$/', $row[2]) || !filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
                $rejected++;
                continue;
            }

            // Already a parent somewhere - the account exists, so record the
            // address on the roster but do not create a second user for it.
            if (User::where('email', $row[1])->where('user_role_id', 3)->exists()) {
                $skipped++;
                $this->rosterRow($school, $row[1], $row[0], $row[2], $row[3], null);
                continue;
            }

            $valid[] = ['name' => $row[0], 'email' => $row[1], 'country_code' => $row[2], 'phone_number' => $row[3]];
        }

        if ($valid === [] && $skipped === 0 && $rejected === 0) {
            return $this->fail('The Excel file contains no usable parent rows.');
        }

        // A blank parent limit means unlimited.
        if ($maxLimit !== null) {
            $remaining = $maxLimit - User::where('school_id', $school->id)->where('user_role_id', 3)->count();

            if ($remaining <= 0) {
                return $this->fail('This school has already reached its parent limit of ' . $maxLimit . '.');
            }

            $skipped += max(0, count($valid) - $remaining);
            $valid = array_slice($valid, 0, $remaining);
        }

        $toMail = [];

        foreach ($valid as $parent) {
            $password = 'Sch' . Str::studly(Str::random(4) . '@1');

            $user = User::create([
                'name' => $parent['name'],
                'email' => $parent['email'],
                'country_code' => $parent['country_code'],
                'phone_no' => $parent['phone_number'],
                'school_id' => $school->id,
                'user_role_id' => 3,
                'user_type' => 'parent',
                'email_verified_at' => now(),
                'is_mobile_verified' => 'yes',
                'password' => Hash::make($password),
            ]);

            $this->grantParentEntitlement($school, $user);
            $this->rosterRow($school, $parent['email'], $parent['name'], $parent['country_code'], $parent['phone_number'], $user->id);

            $toMail[] = [
                'email' => $parent['email'],
                'name' => $parent['name'],
                'password' => $password,
                'school_code' => $school->school_code,
                'school_name' => $school->name,
            ];
        }

        // Queued, never inline: one blocking SMTP call per row times out a
        // large import. Chunked rather than one job for the whole list, so no
        // single job can outlive the worker's timeout and be re-reserved
        // mid-send - that is what mails a parent their password twice.
        SendStudentSignupMail::dispatchInChunks($toMail);

        $imported = count($toMail);

        app(SchoolNotifier::class)->importSummary(
            $school,
            $imported,
            $skipped,
            $rejected,
            (new SchoolSeatService())->remaining($school)
        );

        return [
            'status' => true,
            'message' => "{$imported} parent account(s) created. {$skipped} skipped, {$rejected} rejected.",
            'imported' => $imported,
            'skipped' => $skipped,
            'rejected' => $rejected,
        ];
    }

    /**
     * @return array{status:bool,message:string,imported:int,skipped:int,rejected:int}
     */
    public function importStaff(School $school, UploadedFile $file): array
    {
        $rows = $this->rows($school, $file, 'staff', self::STAFF_HEADER);

        if ($rows === null) {
            return $this->fail('Invalid file format. Please use the provided staff sample.');
        }

        $imported = 0;
        $skipped = 0;
        $rejected = 0;

        foreach ($rows as $row) {
            if (!array_filter($row)) {
                continue;
            }

            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                $rejected++;
                continue;
            }

            if (!filter_var($row[1], FILTER_VALIDATE_EMAIL) || preg_match('/[^a-zA-Z0-9_\-\+]/', $row[4])) {
                $rejected++;
                continue;
            }

            if (User::where('email', $row[1])->exists() || User::where('username', $row[4])->exists()) {
                $skipped++;
                continue;
            }

            $password = 'Tch' . Str::studly(Str::random(4) . '@2');

            User::create([
                'name' => $row[0],
                'email' => $row[1],
                'country_code' => $row[2],
                'phone_no' => $row[3],
                'username' => $row[4],
                'school_id' => $school->id,
                'user_role_id' => 5,
                'user_type' => 'teacher',
                'email_verified_at' => now(),
                'is_mobile_verified' => 'yes',
                'password' => Hash::make($password),
            ]);

            // Teachers are few and imported rarely, so this stays inline. Every
            // token the signup_teacher template declares must be present, or
            // ___mail_sender substitutes the OTP in its place.
            ___mail_sender($row[1], 'signup_teacher', [
                'name' => $row[0],
                'username' => $row[4],
                'password' => $password,
                'school_name' => $school->name,
                'year' => (string) date('Y'),
            ], 'english');

            $imported++;
        }

        if ($imported === 0 && $skipped === 0 && $rejected === 0) {
            return $this->fail('The Excel file contains no usable staff rows.');
        }

        return [
            'status' => true,
            'message' => "{$imported} teacher account(s) created. {$skipped} skipped, {$rejected} rejected.",
            'imported' => $imported,
            'skipped' => $skipped,
            'rejected' => $rejected,
        ];
    }

    /** @return array<int,array>|null null when the header does not match */
    private function rows(School $school, UploadedFile $file, string $kind, array $expected): ?array
    {
        // Original filename is not used in the stored name: it is attacker
        // controlled and was being concatenated straight into a path.
        $path = $file->storeAs('uploads', $school->id . '_' . $kind . '_' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension());

        $data = IOFactory::load(storage_path('app/' . $path))->getActiveSheet()->toArray();
        $header = array_slice((array) array_shift($data), 0, count($expected));

        return $header === $expected ? $data : null;
    }

    private function rosterRow(School $school, string $email, ?string $name, ?string $code, ?string $phone, ?int $userId): void
    {
        $existing = SchoolParentInvite::where('school_id', $school->id)
            ->where('email', SchoolParentInvite::normaliseEmail($email))
            ->first();

        // Never downgrade a claimed entry back to invited.
        if ($existing && $existing->status === 'claimed' && $userId === null) {
            return;
        }

        SchoolParentInvite::updateOrCreate(
            ['school_id' => $school->id, 'email' => SchoolParentInvite::normaliseEmail($email)],
            [
                'name' => $name,
                'country_code' => $code,
                'phone_no' => $phone,
                'status' => $userId ? 'claimed' : 'invited',
                'claimed_user_id' => $userId,
                'invited_at' => now(),
                'claimed_at' => $userId ? now() : null,
            ]
        );
    }

    private function grantParentEntitlement(School $school, User $user): void
    {
        (new SchoolContractService())->grantParentEntitlement($school, $user);
    }

    private function fail(string $message): array
    {
        return ['status' => false, 'message' => $message, 'imported' => 0, 'skipped' => 0, 'rejected' => 0];
    }
}
