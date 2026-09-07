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
        Schema::create('user_liked_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
              ->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('video_id')->nullable();
            $table->foreign('video_id')
            ->references('id')->on('video_contents')->onDelete('cascade');
            $table->enum('type', ['like', 'dislike','favourite']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_liked_videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('video_id');
        });
        Schema::dropIfExists('user_liked_videos');
    }
};
