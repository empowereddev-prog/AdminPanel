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
        Schema::create('battery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('direction',['credit','debit']);
            $table->string('reason');             // quiz, mood, login_bonus, login_penalty, mood_penalty, admin
            $table->integer('points');            // absolute
            $table->date('effective_date');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['user_id','reason','effective_date']);
             
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battery_events');
    }
};
