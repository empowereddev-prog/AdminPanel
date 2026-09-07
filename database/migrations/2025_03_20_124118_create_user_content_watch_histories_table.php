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
        Schema::create('user_content_watch_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->unsignedBigInteger('video_content_id');
            $table->foreign('video_content_id')->references('id')->on('video_contents')->onDelete('cascade');
            $table->string('last_watched_duration')->nullable();
            $table->string('total_video_duration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
            $table->dropConstrainedForeignId('video_content_id');
        });
        Schema::dropIfExists('user_content_watch_histories');
    }
};
