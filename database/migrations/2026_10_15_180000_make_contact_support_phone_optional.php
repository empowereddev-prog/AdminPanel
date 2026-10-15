<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_us', function (Blueprint $table) {
            $table->bigInteger('phone_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Support requests may have no phone number; preserve those records.
    }
};
