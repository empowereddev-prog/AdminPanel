<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One commercial contract per school for Payment History, plus a per-school
 * price on the school row. Parent entitlement grants stay on `subscriptions`
 * and are hidden from the payment list; they must not be used as revenue.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schools') && !Schema::hasColumn('schools', 'price')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('subscription_type');
            });
        }

        if (!Schema::hasTable('school_subscriptions')) {
            Schema::create('school_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->string('subscription_type', 20);
                $table->decimal('price', 10, 2)->default(0);
                $table->string('currency', 8)->default('SGD');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('status', 20)->default('successful');
                $table->unsignedInteger('parent_limit')->nullable();
                $table->unsignedInteger('child_seat_limit')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'status'], 'school_subs_school_status_index');
            });
        }

        $this->backfillExistingSchools();
    }

    public function down(): void
    {
        Schema::dropIfExists('school_subscriptions');

        if (Schema::hasTable('schools') && Schema::hasColumn('schools', 'price')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }

    /**
     * One contract per school that does not already have a row. Price 0 when
     * the school never stored one — do not invent revenue from parent grants.
     */
    private function backfillExistingSchools(): void
    {
        if (!Schema::hasTable('schools') || !Schema::hasTable('school_subscriptions')) {
            return;
        }

        $schools = DB::table('schools')->orderBy('id')->get();

        foreach ($schools as $school) {
            $exists = DB::table('school_subscriptions')->where('school_id', $school->id)->exists();
            if ($exists) {
                continue;
            }

            $type = $school->subscription_type ?: 'monthly';
            $start = Carbon::parse($school->created_at ?? now())->startOfDay();
            $end = match ($type) {
                'quarterly' => $start->copy()->addMonths(3),
                'yearly' => $start->copy()->addYear(),
                default => $start->copy()->addMonth(),
            };

            $price = $school->price ?? 0;

            DB::table('school_subscriptions')->insert([
                'school_id' => $school->id,
                'subscription_type' => $type,
                'price' => $price,
                'currency' => 'SGD',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'successful',
                'parent_limit' => $school->max_limit,
                'child_seat_limit' => $school->child_seat_limit ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
