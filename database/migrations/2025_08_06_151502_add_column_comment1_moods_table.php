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
        Schema::table('moods', function (Blueprint $table) {
            $table->longText('comment1')->nullable();
            $table->longText('comment2')->nullable();
            $table->longText('comment3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moods', function (Blueprint $table) {
            $table->dropColumn(['comment1', 'comment2', 'comment3']);
        });
    }
};
