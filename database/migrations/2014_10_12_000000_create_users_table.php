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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('user_type', ['user', 'admin', 'parent','teacher'])->nullable();
            $table->string('user_role_id')->nullable();
            $table->string('country_code')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('gender')->nullable();
            $table->string('password')->nullable();
            $table->enum('terms_n_conditions_accepted',['yes','no'])->default('yes');
            $table->string('otp')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->string('profile_image')->nullable();
            $table->enum('is_verified', [0, 1])->default(0);
            $table->string('mobile_otp')->nullable();
            $table->enum('is_mobile_verified', [0, 1])->default(0);
            $table->string('password_reset_code')->nullable();
            $table->string('password_reset_expires_at')->nullable();
            $table->string('device_token')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('language', ['english', 'chinese'])->default('english');
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
