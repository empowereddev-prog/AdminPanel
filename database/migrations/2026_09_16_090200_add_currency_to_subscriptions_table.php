<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * subscriptions.currency is written by RegisterService::register and by both
 * import loops in SchoolController, and no migration has ever created it - the
 * same hand-patched-in-production drift the schools table had.
 *
 * The effect is that school sign-up fails outright on any database built from
 * this repo: the insert throws "Unknown column 'currency'", which
 * HomeApiController::register's blanket catch turns into a generic "failed to
 * register". Surfaced by the roster feature tests, which are the first tests to
 * exercise that path.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions') || Schema::hasColumn('subscriptions', 'currency')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            // Every writer hardcodes 'SGD' today; nullable rather than defaulted
            // so an existing row is not retroactively labelled with a currency
            // nobody recorded at the time.
            $table->string('currency', 3)->nullable()->after('price');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'currency')) {
            Schema::table('subscriptions', fn (Blueprint $table) => $table->dropColumn('currency'));
        }
    }
};
