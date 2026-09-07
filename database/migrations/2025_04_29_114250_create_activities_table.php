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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mood_id');
            $table->foreign('mood_id')
              ->references('id')->on('moods')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('name_chinese')->nullable();
            $table->unsignedBigInteger('points')->default(0);
            $table->enum('status',['active','inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mood_id');
        });
        Schema::dropIfExists('activities');
    }
};
