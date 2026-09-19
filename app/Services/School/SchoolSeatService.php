<?php

namespace App\Services\School;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Counts and caps the child accounts a school's parents have created.
 *
 * A child is a users row with user_role_id = 4 and parent_id set, and its
 * school_id is always NULL - which is why max_limit has never counted one.
 * The count is derived through the parent rather than denormalised onto the
 * child, because writing school_id onto children would silently change
 * SchoolController's parent counts, HomeApiController::login's
 * whereNull('school_id'), the deactivation cascade and the admin user list all
 * at once.
 */
class SchoolSeatService
{
    /**
     * Children created by this school's parents. Soft-deleted rows on either
     * side are excluded, which is what makes deleting a child free its seat.
     */
    public function childCountForSchool(int $schoolId): int
    {
        // DB::table, not Eloquent: the SoftDeletes global scope would apply to
        // only one side of a self-join, so both deleted_at predicates are
        // spelled out here instead.
        return (int) DB::table('users as c')
            ->join('users as p', 'p.id', '=', 'c.parent_id')
            ->where('p.school_id', $schoolId)
            ->where('p.user_role_id', 3)
            ->where('c.user_role_id', 4)
            ->whereNull('c.deleted_at')
            ->whereNull('p.deleted_at')
            ->count();
    }

    public function remaining(School $school): ?int
    {
        if ($school->child_seat_limit === null) {
            return null;
        }

        return max(0, (int) $school->child_seat_limit - $this->childCountForSchool($school->id));
    }

    /**
     * May this parent add one more child right now?
     *
     * Takes the school row lock itself, so the caller must already be inside a
     * transaction - see ChildController::addChild. Matches the
     * AvtarController::unlock idiom: lock, check, then write.
     *
     * @return array{allowed:bool,reason:string,message:?string,meta:array}
     */
    public function checkChildSeat(User $parent, string $language = 'english'): array
    {
        // Consumer parent. Zero queries, provably unchanged behaviour.
        if (empty($parent->school_id)) {
            return $this->allow('no_school');
        }

        $school = School::whereKey($parent->school_id)->lockForUpdate()->first();

        // School row gone, or neither limit configured: today's behaviour, and
        // no count query is run.
        if (!$school || ($school->child_seat_limit === null && $school->per_parent_child_limit === null)) {
            return $this->allow('no_limit');
        }

        // Per-parent first: it is scoped to one parent and avoids the
        // school-wide scan when the cheaper limit already answers.
        if ($school->per_parent_child_limit !== null) {
            $mine = $parent->child()->count();

            if ($mine >= (int) $school->per_parent_child_limit) {
                return $this->deny('parent_seats_full', $language, [
                    'used' => $mine,
                    'limit' => (int) $school->per_parent_child_limit,
                ]);
            }
        }

        if ($school->child_seat_limit !== null) {
            $used = $this->childCountForSchool($school->id);

            if ($used >= (int) $school->child_seat_limit) {
                return $this->deny('school_seats_full', $language, [
                    'used' => $used,
                    'limit' => (int) $school->child_seat_limit,
                ]);
            }
        }

        return $this->allow('ok');
    }

    /**
     * Snapshot for the additive getProfile `seats` block and the admin screen.
     *
     * @return array<string,int|null>
     */
    public function summaryForSchool(School $school): array
    {
        $used = $this->childCountForSchool($school->id);

        return [
            'child_seat_limit' => $school->child_seat_limit !== null ? (int) $school->child_seat_limit : null,
            'child_seats_used' => $used,
            'child_seats_remaining' => $school->child_seat_limit !== null
                ? max(0, (int) $school->child_seat_limit - $used)
                : null,
            'per_parent_child_limit' => $school->per_parent_child_limit !== null
                ? (int) $school->per_parent_child_limit
                : null,
            'parent_limit' => $school->max_limit !== null ? (int) $school->max_limit : null,
            'parents_used' => User::where('school_id', $school->id)->where('user_role_id', 3)->count(),
        ];
    }

    private function allow(string $reason, array $meta = []): array
    {
        return ['allowed' => true, 'reason' => $reason, 'message' => null, 'meta' => $meta];
    }

    /**
     * Distinct messages: a parent needs to know whether to remove one of their
     * own children or to contact the school.
     */
    private function deny(string $reason, string $language, array $meta): array
    {
        $message = $reason === 'parent_seats_full'
            ? ($language === 'english'
                ? 'You have reached the maximum number of children your school allows per parent.'
                : '您已达到学校允许每位家长添加的孩子数量上限。')
            : ($language === 'english'
                ? 'Your school has used all of its available child places. Please contact your school administrator.'
                : '贵校的儿童名额已全部使用。请联系您的学校管理员。');

        return ['allowed' => false, 'reason' => $reason, 'message' => $message, 'meta' => $meta];
    }
}
