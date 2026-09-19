<?php

namespace App\Services\School;

use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Decides whether an email address is allowed to join a school.
 *
 * Returns arrays rather than throwing, matching RegisterService. The three call
 * sites have three different error contracts that an exception handler cannot
 * reconcile - register answers HTTP 200 with status:false under v1, addChild
 * uses 201, updateParentProfile uses 422 - and HomeApiController::register
 * wraps its call in a blanket catch(\Throwable) that would swallow a domain
 * exception into a generic message.
 */
class SchoolRosterService
{
    /**
     * Is this email allowed to join this school by code?
     *
     * @return array{allowed:bool,reason:string,message:?string,meta:array}
     */
    public function checkJoin(School $school, ?string $email, string $language = 'english'): array
    {
        // Short-circuit 1: the school has switched self sign-up off entirely.
        if (($school->self_signup_enabled ?? 'yes') === 'no') {
            return $this->deny('self_signup_disabled', $language);
        }

        // Short-circuit 2: the flag-off guarantee. No queries, no behaviour
        // change. The ?? 'no' matters - on a database where the migration has
        // not run, or on a partially selected model, the property is null and
        // this must fall through to legacy behaviour rather than deny.
        if (($school->enforce_parent_roster ?? 'no') !== 'yes') {
            return $this->allow('roster_not_enforced');
        }

        $normalised = SchoolParentInvite::normaliseEmail($email);

        if ($normalised === null) {
            return $this->deny('not_on_roster', $language);
        }

        $invite = SchoolParentInvite::where('school_id', $school->id)
            ->where('email', $normalised)
            ->first();

        if (!$invite) {
            return $this->deny('not_on_roster', $language);
        }

        if ($invite->status === 'revoked') {
            return $this->deny('revoked', $language);
        }

        if ($invite->isClaimed()) {
            return $this->deny('already_claimed', $language);
        }

        return $this->allow('ok', [
            'invite_id' => $invite->id,
            'child_seat_allocation' => $invite->child_seat_allocation,
        ]);
    }

    /**
     * Bind the invite to the user who just claimed it.
     *
     * Call inside the caller's transaction: the row lock here plus that
     * transaction is what stops two people claiming one leaked address.
     */
    public function claim(School $school, User $user): ?SchoolParentInvite
    {
        if (($school->enforce_parent_roster ?? 'no') !== 'yes') {
            return null;
        }

        $normalised = SchoolParentInvite::normaliseEmail($user->email);

        if ($normalised === null) {
            return null;
        }

        $invite = SchoolParentInvite::where('school_id', $school->id)
            ->where('email', $normalised)
            ->lockForUpdate()
            ->first();

        if (!$invite || $invite->status === 'revoked') {
            return null;
        }

        // Already ours: rerun safety. Already someone else's: the caller
        // checked, this is the narrow race guard - write nothing.
        if ($invite->claimed_user_id !== null) {
            return (int) $invite->claimed_user_id === (int) $user->id ? $invite : null;
        }

        $invite->update([
            'status' => 'claimed',
            'claimed_user_id' => $user->id,
            'claimed_at' => now(),
        ]);

        return $invite;
    }

    /**
     * Return a removed parent's seat to the roster, so the school can re-issue
     * it without an admin re-adding the address by hand.
     */
    public function release(User $user): void
    {
        if (empty($user->school_id)) {
            return;
        }

        SchoolParentInvite::where('school_id', $user->school_id)
            ->where('claimed_user_id', $user->id)
            ->where('status', '!=', 'revoked')
            ->update([
                'status' => 'invited',
                'claimed_user_id' => null,
                'claimed_at' => null,
            ]);
    }

    /**
     * @return array{status:bool,message:string}
     */
    public function revoke(SchoolParentInvite $invite): array
    {
        $invite->update([
            'status' => 'revoked',
            'claimed_user_id' => null,
            'claimed_at' => null,
        ]);

        return ['status' => true, 'message' => 'Roster entry revoked.'];
    }

    /**
     * Reverse of revoke. If the parent account is still at this school, the
     * invite is claimed again; otherwise it goes back to invited so they can
     * register with the school code.
     *
     * @return array{status:bool,message:string}
     */
    public function restore(SchoolParentInvite $invite): array
    {
        if ($invite->status !== 'revoked') {
            return ['status' => false, 'message' => 'That entry is not revoked.'];
        }

        $user = User::where('school_id', $invite->school_id)
            ->where('user_role_id', 3)
            ->whereRaw('LOWER(email) = ?', [$invite->email])
            ->first();

        if ($user) {
            $invite->update([
                'status' => 'claimed',
                'claimed_user_id' => $user->id,
                'claimed_at' => now(),
            ]);

            return ['status' => true, 'message' => 'Roster access restored. This parent can use the school code again.'];
        }

        $invite->update([
            'status' => 'invited',
            'claimed_user_id' => null,
            'claimed_at' => null,
        ]);

        return ['status' => true, 'message' => 'Roster entry restored. They can register with the school code again.'];
    }

    /**
     * Records the rejected attempts and tells the school its code is
     * circulating - throttled to one notice per school per hour, because
     * QUEUE_CONNECTION means this send is inline on the register request.
     */
    public function recordOffRosterAttempt(School $school, ?string $email, string $reason): void
    {
        Log::warning('School roster join rejected', [
            'school_id' => $school->id,
            'email' => SchoolParentInvite::normaliseEmail($email),
            'reason' => $reason,
        ]);

        app(SchoolNotifier::class)->offRosterAttempt($school, $email);
    }

    /**
     * @return array{allowed:true,reason:string,message:null,meta:array}
     */
    private function allow(string $reason, array $meta = []): array
    {
        return ['allowed' => true, 'reason' => $reason, 'message' => null, 'meta' => $meta];
    }

    /**
     * Every roster denial returns the SAME user-facing message. `reason` is
     * internal only. A message that distinguished "not on the roster" from
     * "already claimed" would turn this endpoint into a roster oracle.
     */
    private function deny(string $reason, string $language): array
    {
        $message = $reason === 'self_signup_disabled'
            ? ($language === 'english'
                ? 'This school does not accept self sign-up. Please contact your school administrator.'
                : '该学校不接受自助注册。请联系您的学校管理员。')
            : ($language === 'english'
                ? 'This school code cannot be used with this email address. Please use the email address your school registered for you, or contact your school.'
                : '此学校代码无法与该电子邮件地址一起使用。请使用学校为您登记的电子邮件地址，或联系您的学校。');

        return ['allowed' => false, 'reason' => $reason, 'message' => $message, 'meta' => []];
    }
}
