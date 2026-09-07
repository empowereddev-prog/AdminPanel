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
        Schema::create('user_avtar_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
            $table->string('background_id')->nullable();            
            $table->string('head_id')->nullable();            
            $table->string('body_id')->nullable();            
            $table->string('glass_id')->nullable();            
            $table->string('hat_id')->nullable();            
            $table->string('eye_id')->nullable();     
            $table->string('extra_accessories_id')->nullable();                   
            $table->string('image')->nullable();                   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_avtar_images', function (Blueprint $table) {
            $table->dropConstrainedForeignId('child_id');
        });
        Schema::dropIfExists('user_avtar_images');
       
    }
};
