<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('schools')->where('account_mode', 'parent')->update(['account_mode' => 'linked']);
        DB::table('schools')->where('account_mode', 'child')->update(['account_mode' => 'independent']);
        Schema::table('schools', fn (Blueprint $table) => $table->string('account_mode', 16)->default('linked')->change());
    }

    public function down(): void
    {
        DB::table('schools')->where('account_mode', 'linked')->update(['account_mode' => 'parent']);
        DB::table('schools')->where('account_mode', 'independent')->update(['account_mode' => 'child']);
        Schema::table('schools', fn (Blueprint $table) => $table->string('account_mode', 16)->default('parent')->change());
    }
};
