<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment History had no durable way to tell a school-onboarded parent from a
 * real App Store buyer. It inferred one from price, because
 * SchoolContractService::grantParentEntitlement() writes '0'.
 *
 * That only holds for rows written by the current code. The onboarding loops
 * that were later commented out (SchoolController::store / update) wrote the
 * real retail price - 13.49 / 33.81 / 101.63 - onto every imported parent, so
 * those rows pass a price > 0 test and still appear on the revenue report as
 * if the parent had paid.
 *
 * This column records the origin instead of inferring it, and backfills the
 * legacy rows from the one signal that is reliable: a verified purchase
 * carries a transaction_id and a receipt (ChildController::subscription), and
 * a grant never does.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        if (!Schema::hasColumn('subscriptions', 'source')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->enum('source', ['iap', 'school_grant'])
                    ->default('iap')
                    ->after('subscription_type_id');
                $table->index('source');
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'source')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex(['source']);
                $table->dropColumn('source');
            });
        }
    }

    /**
     * Idempotent - re-running only ever re-marks rows that already match.
     */
    private function backfill(): void
    {
        // A parent attached to a school whose row carries no proof of purchase
        // was onboarded by that school, whatever price the old code stamped on
        // it. Receipt and transaction_id are the App Store's own evidence.
        DB::table('subscriptions')
            ->join('users', 'users.id', '=', 'subscriptions.user_id')
            ->whereNotNull('users.school_id')
            ->where(function ($q) {
                $q->whereNull('subscriptions.transaction_id')
                    ->orWhere('subscriptions.transaction_id', '');
            })
            ->where(function ($q) {
                $q->whereNull('subscriptions.receipt')
                    ->orWhere('subscriptions.receipt', '');
            })
            ->update(['subscriptions.source' => 'school_grant']);

        // A zero-price row can only be a grant - nobody buys a plan for nothing
        // - so it is marked regardless of whether the user still has a school.
        DB::table('subscriptions')
            ->whereRaw("CAST(COALESCE(NULLIF(price, ''), '0') AS DECIMAL(10,2)) <= 0")
            ->update(['source' => 'school_grant']);
    }
};
