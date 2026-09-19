<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * One row per parent email address a school has named.
 *
 * This is the allowlist that decides who may join a school, so it is audited:
 * who was added, who was revoked and when is a question the school will ask.
 */
class SchoolParentInvite extends Model implements AuditableContract
{
    use HasFactory, Auditable;

    protected $guarded = [];

    protected $casts = [
        'invited_at' => 'datetime',
        'claimed_at' => 'datetime',
    ];

    /**
     * Every read and write of `email` must go through this.
     *
     * The unique index is on (school_id, email) with no case folding of its
     * own, and the imported spreadsheets contain mixed casing and stray
     * whitespace. Without one normalisation point, "A@x.com" and "a@x.com"
     * become two roster rows for one parent and the single-claim guarantee
     * quietly stops holding.
     */
    public static function normaliseEmail(?string $email): ?string
    {
        $email = trim((string) $email);

        return $email === '' ? null : mb_strtolower($email);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function claimedUser()
    {
        return $this->belongsTo(User::class, 'claimed_user_id');
    }

    public function scopeForEmail(Builder $query, ?string $email): Builder
    {
        return $query->where('email', self::normaliseEmail($email));
    }

    /**
     * Rows that a registering parent could still take: never claimed, or
     * claimed by nobody. `revoked` is excluded deliberately.
     */
    public function scopeClaimable(Builder $query): Builder
    {
        return $query->where('status', '!=', 'revoked')
            ->where(function (Builder $q) {
                $q->whereNull('claimed_user_id')->orWhere('status', 'invited');
            });
    }

    public function isClaimed(): bool
    {
        return $this->status === 'claimed' && $this->claimed_user_id !== null;
    }
}
