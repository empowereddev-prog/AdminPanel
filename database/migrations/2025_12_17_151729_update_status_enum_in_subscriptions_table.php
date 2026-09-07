<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('subscriptions', 'status')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->enum('status', [
                    'successful',
                    'unsuccessful',
                    'cancelled',
                    'expired',
                    'revoked',
                ])->default('successful');
            });
            return;
        }

        DB::statement("
            ALTER TABLE subscriptions 
            MODIFY status ENUM(
                'successful',
                'unsuccessful',
                'cancelled',
                'expired',
                'revoked'
            ) NOT NULL DEFAULT 'successful'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE subscriptions 
            MODIFY status ENUM(
                'successful',
                'unsuccessful'
            ) NOT NULL DEFAULT 'successful'
        ");
    }
};
