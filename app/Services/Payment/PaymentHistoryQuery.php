<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\DB;

/**
 * The single definition of what counts as a payment.
 *
 * It lives here because it had drifted: the Payment History list built the
 * union itself while the dashboard tile ran a bare count on `subscriptions`,
 * so the tile showed school-onboarded parents the list had already excluded
 * and showed no school contracts at all. Anything that reports on revenue
 * should start from build().
 */
class PaymentHistoryQuery
{
    /**
     * Direct consumer purchases, union school contracts.
     *
     * A parent who belongs to a school is never a payment line of their own -
     * the school is the payer of record for everyone it onboards, and its row
     * on school_subscriptions is the single line for that revenue.
     *
     * subscriptions.source is the durable marker written by
     * SchoolContractService::grantParentEntitlement(); it also catches a grant
     * whose parent was later detached from the school. Price is kept as a
     * third guard because it is what every pre-existing row was written
     * against. The column is varchar, hence the explicit cast.
     */
    public function build()
    {
        $iap = DB::table('subscriptions')
            ->leftJoin('users', 'subscriptions.user_id', '=', 'users.id')
            ->whereNull('users.school_id')
            ->where(function ($q) {
                $q->whereNull('subscriptions.source')
                    ->orWhere('subscriptions.source', '!=', 'school_grant');
            })
            ->whereRaw('CAST(subscriptions.price AS DECIMAL(10,2)) > 0')
            ->select([
                DB::raw("CONCAT('iap-', subscriptions.id) as payment_key"),
                'users.name as payer_name',
                'subscriptions.subscription_type',
                'subscriptions.price',
                'subscriptions.start_date',
                'subscriptions.status',
                'subscriptions.created_at',
            ]);

        $schools = DB::table('school_subscriptions')
            ->join('schools', 'school_subscriptions.school_id', '=', 'schools.id')
            ->select([
                DB::raw("CONCAT('sch-', school_subscriptions.id) as payment_key"),
                'schools.name as payer_name',
                'school_subscriptions.subscription_type',
                'school_subscriptions.price',
                'school_subscriptions.start_date',
                'school_subscriptions.status',
                'school_subscriptions.created_at',
            ]);

        return DB::query()->fromSub($iap->unionAll($schools), 'payment_rows');
    }

    /**
     * The dashboard tile. Both casings exist because the two tables were
     * written by different code paths - the list filter has the same branch.
     */
    public function successfulCount(): int
    {
        return $this->build()->whereIn('status', ['successful', 'Successful'])->count();
    }
}
