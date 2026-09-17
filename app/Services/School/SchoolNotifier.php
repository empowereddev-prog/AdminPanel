<?php

namespace App\Services\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use Illuminate\Support\Facades\Cache;

/**
 * Every school-facing email in this flow, in one place.
 *
 * It exists mainly so the token contract lives next to the send. ___mail_sender
 * substitutes $data[$key] ?? $data['otp'] ?? $data['code'] ?? '', so a token
 * declared in the template but missing from the payload silently renders
 * whatever OTP happens to be in scope. Building the payloads here - rather than
 * inline at four call sites - keeps that contract checkable.
 */
class SchoolNotifier
{
    /** One notice per school per hour. */
    private const OFF_ROSTER_TTL = 3600;

    /**
     * Tells a school its code is circulating outside its parent list.
     *
     * Throttled hard, and for two separate reasons: an attacker working through
     * a leaked code would otherwise mailbomb the school, and because the queue
     * runs this inline it would also add an SMTP round-trip to every rejected
     * registration.
     */
    public function offRosterAttempt(School $school, ?string $attemptedEmail): void
    {
        if (empty($school->email)) {
            return;
        }

        $bucket = "offroster_notice:{$school->id}";

        // Attempts inside the window are accumulated so the notice can report
        // "4 attempts" rather than arriving four times.
        $pending = Cache::get($bucket . ':pending', []);
        $email = SchoolParentInvite::normaliseEmail($attemptedEmail);

        if ($email !== null && !in_array($email, $pending, true)) {
            $pending[] = $email;
        }

        Cache::put($bucket . ':pending', array_slice($pending, 0, 20), self::OFF_ROSTER_TTL);

        // Cache::add is the throttle: it only succeeds for the first caller in
        // the window, so exactly one send happens per school per hour.
        if (!Cache::add($bucket, true, self::OFF_ROSTER_TTL)) {
            return;
        }

        ___mail_sender($school->email, 'school_offroster_attempt', [
            'school_name' => (string) $school->name,
            'attempt_count' => (string) max(1, count($pending)),
            'attempted_emails' => $pending === [] ? 'not recorded' : implode(', ', $pending),
            'window' => 'hour',
            'year' => (string) date('Y'),
        ], 'english');
    }

    /**
     * Fires as a school crosses 90% and again at 100% of its child places.
     * Each threshold notifies once per term of the cache entry, so a school
     * hovering at the boundary is not mailed on every add-child call.
     */
    public function seatThreshold(School $school, int $used, int $limit): void
    {
        if (empty($school->email) || $limit <= 0) {
            return;
        }

        $percent = (int) floor(($used / $limit) * 100);
        $threshold = $percent >= 100 ? 100 : ($percent >= 90 ? 90 : null);

        if ($threshold === null) {
            return;
        }

        if (!Cache::add("seat_threshold:{$school->id}:{$threshold}", true, 86400)) {
            return;
        }

        ___mail_sender($school->email, 'school_seat_threshold', [
            'school_name' => (string) $school->name,
            'seats_used' => (string) $used,
            'seats_limit' => (string) $limit,
            'percent' => (string) min(100, $percent),
            'year' => (string) date('Y'),
        ], 'english');
    }

    /**
     * Sent to the school contact once an admin's parent import finishes, so the
     * counts the admin sees on screen also reach the school that supplied the
     * list.
     */
    public function importSummary(School $school, int $imported, int $skipped, int $failed, ?int $seatsRemaining): void
    {
        if (empty($school->email)) {
            return;
        }

        ___mail_sender($school->email, 'school_import_summary', [
            'school_name' => (string) $school->name,
            'imported' => (string) $imported,
            'skipped' => (string) $skipped,
            'failed' => (string) $failed,
            'seats_remaining' => $seatsRemaining === null ? 'Unlimited' : (string) $seatsRemaining,
            'year' => (string) date('Y'),
        ], 'english');
    }

    /**
     * Sent to the school's contact address as soon as the school is created.
     *
     * store() has always collected schools.email and then never used it, so a
     * newly onboarded school received nothing at all - no code, no plan, no
     * record of what it had been given.
     */
    public function schoolOnboarded(School $school): void
    {
        if (empty($school->email)) {
            return;
        }

        ___mail_sender($school->email, 'school_onboarded', [
            'school_name' => (string) $school->name,
            'school_code' => (string) $school->school_code,
            'subscription_type' => ucfirst((string) ($school->subscription_type ?: 'Not set')),
            'parent_limit' => $school->max_limit !== null ? (string) $school->max_limit : 'Unlimited',
            'child_seat_limit' => $school->child_seat_limit !== null ? (string) $school->child_seat_limit : 'Unlimited',
            'year' => (string) date('Y'),
        ], 'english');
    }

    /**
     * The roster invitation an admin sends or resends from the school screen.
     */
    public function parentInvited(School $school, SchoolParentInvite $invite): void
    {
        ___mail_sender($invite->email, 'school_parent_invited', [
            'name' => (string) ($invite->name ?: 'there'),
            'school_name' => (string) $school->name,
            'school_code' => (string) $school->school_code,
            // The deeplink web fallback resolves to the right store for
            // whichever device opens it, so one link serves iOS and Android.
            'app_link' => (string) config('deeplink.web_fallback_url', config('app.url')),
            'year' => (string) date('Y'),
        ], 'english');
    }
}
