<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendSchoolParentCodeMail;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\SchoolParentInvite;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Gets the school code to parents: a bulk email (optionally scheduled) and a
 * printable letter the school can send home. Both authorise against the
 * existing School Management menu (3), like the student import.
 */
class SchoolParentCodeController extends Controller
{
    public const MAX_RECIPIENTS = 500;

    private function authorizeSchool(): void
    {
        $permission = PermissionUser::checkpermission(auth()->id(), 3);
        abort_unless($permission && $permission->is_modify === 'yes', 403);
    }

    /**
     * Split a pasted block of addresses on whitespace, commas and semicolons.
     *
     * @return array{valid:array<int,string>,invalid:array<int,string>}
     */
    public static function parseEmails(string $input): array
    {
        $valid = [];
        $invalid = [];
        foreach (preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY) as $candidate) {
            $email = mb_strtolower(trim($candidate));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 191) {
                $valid[$email] = $email;
            } else {
                $invalid[$candidate] = $candidate;
            }
        }

        return ['valid' => array_values($valid), 'invalid' => array_values($invalid)];
    }

    public function send(Request $request, $id)
    {
        $this->authorizeSchool();
        $data = $request->validate([
            'parent_emails' => 'required|string|max:60000',
            'send_at' => 'nullable|date|after:now',
        ]);
        $school = School::findOrFail($id);

        if ($school->status !== 'active') {
            return back()->with('error', 'The school is not active, so its code cannot be used yet.');
        }
        if (($school->self_signup_enabled ?? 'yes') === 'no') {
            return back()->with('error', 'Sign-up with the school code is switched off for this school. Turn it on first.');
        }

        $parsed = self::parseEmails($data['parent_emails']);
        if ($parsed['valid'] === []) {
            return back()->withInput()->with('error', 'No valid email addresses were found.');
        }
        if (count($parsed['valid']) > self::MAX_RECIPIENTS) {
            return back()->withInput()->with('error', 'Please send to at most ' . self::MAX_RECIPIENTS . ' addresses at a time.');
        }

        // With "only roster emails may join" on, the code alone is not enough: the
        // address has to be on the school's list. Add each recipient to it.
        if (($school->enforce_parent_roster ?? 'no') === 'yes') {
            foreach ($parsed['valid'] as $email) {
                SchoolParentInvite::firstOrCreate(
                    ['school_id' => $school->id, 'email' => SchoolParentInvite::normaliseEmail($email)],
                    ['status' => 'invited', 'invited_at' => now()]
                );
            }
        }

        $sendAt = !empty($data['send_at']) ? Carbon::parse($data['send_at']) : null;
        SendSchoolParentCodeMail::dispatchInChunks($parsed['valid'], (string) $school->name, (string) $school->school_code, $sendAt);

        $message = count($parsed['valid']) . ' parent email(s) '
            . ($sendAt ? 'scheduled for ' . $sendAt->format('d M Y, H:i') : 'queued for sending') . '.';
        if ($parsed['invalid']) {
            $message .= ' Skipped invalid address(es): ' . implode(', ', array_slice($parsed['invalid'], 0, 5))
                . (count($parsed['invalid']) > 5 ? '…' : '') . '.';
        }

        return back()->with('success', $message);
    }

    /** A one-page letter with the code, the QR and the sign-up steps. */
    public function letter($id)
    {
        $this->authorizeSchool();
        $school = School::findOrFail($id);
        $qr = 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents(resource_path('images/email/teacher-app-download-qr.jpeg')));

        return Pdf::loadView('pdf.school-parent-letter', [
            'school' => $school,
            'qr' => $qr,
            'website' => (string) config('deeplink.web_fallback_url', 'https://empoweredhealth.asia/'),
        ])->setPaper('a4')->download('empowered-parent-letter-' . $school->school_code . '.pdf');
    }
}
