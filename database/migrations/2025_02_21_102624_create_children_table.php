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
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedBigInteger('parent_id');
            $table->date('dob');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->string('loyalty_points')->nullable();
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('is_primary', ['yes', 'no'])->default('no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
