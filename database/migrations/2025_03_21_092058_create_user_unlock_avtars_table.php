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
        Schema::create('user_unlock_avtars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->string('type')->nullable();            
            $table->unsignedBigInteger('type_id')->nullable(); // Fixed `bigInt` issue
            $table->enum('is_available',['yes','no'])->nullable();
            $table->unsignedBigInteger('points')->nullable();            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_unlock_avtars', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });
        Schema::dropIfExists('user_unlock_avtars');
    }
};
