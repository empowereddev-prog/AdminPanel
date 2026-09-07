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
        Schema::create('permission_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
              ->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('menu_id');
            $table->foreign('menu_id')
            ->references('id')->on('admin_menu')->onDelete('cascade');
            $table->enum('is_view',['yes','no'])->default('yes');
            $table->enum('is_modify',['yes','no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permission_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('menu_id');
        });
        Schema::dropIfExists('permission_users');
    }
};
