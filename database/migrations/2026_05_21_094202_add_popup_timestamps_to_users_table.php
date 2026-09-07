<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('popup_1')->nullable();
            $table->string('popup_2')->nullable();
            $table->timestamp('popup_1_updated_at')->nullable();
            $table->timestamp('popup_2_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'popup_1_updated_at',
                'popup_2_updated_at'
            ]);
        });
    }
};
