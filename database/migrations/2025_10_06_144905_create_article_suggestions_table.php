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
        Schema::create('article_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('knowledge_session_id')->nullable();
            $table->foreign('knowledge_session_id')
                ->references('id')->on('knowledge_sessions')->onDelete('cascade');
            $table->string('article_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_suggestions');
    }
};
