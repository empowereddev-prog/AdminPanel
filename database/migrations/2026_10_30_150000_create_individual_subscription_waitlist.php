<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('individual_subscription_waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('source', 32)->default('homepage');
            $table->timestamps();
        });

        // Register the module in the existing permission picker. No sub-admin
        // permissions are granted by this migration.
        DB::table('admin_menu')->insert([
            'menu_name' => 'Individual Subscription Waitlist',
            'pid' => 0,
            'id_general_status' => 1,
        ]);
    }

    public function down(): void
    {
        $menuIds = DB::table('admin_menu')
            ->where('menu_name', 'Individual Subscription Waitlist')->pluck('id');
        DB::table('permission_users')->whereIn('menu_id', $menuIds)->delete();
        DB::table('admin_menu')->whereIn('id', $menuIds)->delete();
        Schema::dropIfExists('individual_subscription_waitlist');
    }
};
