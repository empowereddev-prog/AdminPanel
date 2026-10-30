<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IndividualSubscriptionWaitlist extends Model
{
    public const MENU_NAME = 'Individual Subscription Waitlist';

    protected $table = 'individual_subscription_waitlist';

    protected $fillable = ['email', 'source'];

    public static function canView(?User $user): bool
    {
        if (!$user || $user->status !== 'active') {
            return false;
        }

        if ((int) $user->user_role_id === 1) {
            return true;
        }

        // Reuse the existing role-permission screen without granting access
        // automatically to any existing sub-administrator.
        return DB::table('permission_users')
            ->join('admin_menu', 'admin_menu.id', '=', 'permission_users.menu_id')
            ->where('permission_users.user_id', $user->id)
            ->where('admin_menu.menu_name', self::MENU_NAME)
            ->where('permission_users.is_view', 'yes')
            ->exists();
    }
}
