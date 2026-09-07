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
        Schema::create('user_articale_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('article_id')->nullable();
            $table->foreign('article_id')
                ->references('id')->on('knowledge_sessions')->onDelete('cascade');
            $table->enum('type', ['like', 'dislike', 'favourite']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_articale_likes');
    }
};
