<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
       public function up()
    {
        Schema::table('video_contents', function (Blueprint $table) {
            $table->string('mood')->nullable();
            $table->text('other')->nullable();
        });
    }

    public function down()
    {
        Schema::table('video_contents', function (Blueprint $table) {
            $table->dropColumn(['mood', 'other']);
        });
    }

};
