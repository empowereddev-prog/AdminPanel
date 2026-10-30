<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IndividualSubscriptionWaitlistTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'waitlist_test',
            'database.connections.waitlist_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'cache.default' => 'array',
            'mail.default' => 'array',
        ]);
        Mail::fake();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedInteger('user_role_id');
            $table->string('status')->default('active');
            $table->softDeletes();
        });
        Schema::create('admin_menu', function (Blueprint $table) {
            $table->id();
            $table->string('menu_name');
            $table->integer('pid');
            $table->integer('id_general_status');
        });
        Schema::create('permission_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('menu_id');
            $table->string('is_view')->default('no');
            $table->string('is_modify')->default('no');
        });
        (require database_path('migrations/2026_10_30_150000_create_individual_subscription_waitlist.php'))->up();
    }

    public function test_public_registration_normalizes_email_and_never_creates_an_account_or_sends_mail(): void
    {
        $this->postJson('/api/individual-subscription-waitlist', ['email' => '  Teen@Example.com  ', 'source' => 'other'])
            ->assertCreated()->assertJsonPath('status', 'registered');
        $this->assertDatabaseHas('individual_subscription_waitlist', ['email' => 'teen@example.com', 'source' => 'homepage']);
        $this->assertDatabaseCount('users', 0);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_duplicate_case_variants_count_only_once(): void
    {
        $this->postJson('/api/individual-subscription-waitlist', ['email' => 'teen@example.com'])->assertCreated();
        $this->postJson('/api/individual-subscription-waitlist', ['email' => 'TEEN@example.com'])
            ->assertOk()->assertJsonPath('status', 'already_registered');
        $this->assertDatabaseCount('individual_subscription_waitlist', 1);
    }

    public function test_invalid_and_honeypot_submissions_are_not_saved(): void
    {
        foreach (['', 'not-an-email', ['bad']] as $email) {
            $this->postJson('/api/individual-subscription-waitlist', ['email' => $email])->assertStatus(422);
        }
        $this->postJson('/api/individual-subscription-waitlist', ['email' => 'bot@example.com', 'website' => 'spam'])
            ->assertStatus(422);
        $this->assertDatabaseCount('individual_subscription_waitlist', 0);
    }

    public function test_signup_rate_limit_is_enforced(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/individual-subscription-waitlist', ['email' => "teen{$i}@example.com"])->assertCreated();
        }
        $this->postJson('/api/individual-subscription-waitlist', ['email' => 'sixth@example.com'])->assertStatus(429);
        $this->assertDatabaseCount('individual_subscription_waitlist', 5);
    }

    public function test_guest_cannot_read_the_waitlist(): void
    {
        $this->getJson('/individual-subscription-waitlist')->assertUnauthorized();
    }

    public function test_superadmin_sees_total_and_paginated_registrations(): void
    {
        DB::table('users')->insert(['id' => 1, 'email' => 'admin@example.com', 'user_role_id' => 1, 'status' => 'active']);
        $this->postJson('/api/individual-subscription-waitlist', ['email' => 'teen@example.com'])->assertCreated();
        $this->actingAs(User::find(1), 'admin')->getJson('/individual-subscription-waitlist')
            ->assertOk()->assertJsonPath('total_unique', 1)->assertJsonPath('registrations.data.0.email', 'teen@example.com');
        $this->getJson('/individual-subscription-waitlist?search=missing')
            ->assertOk()->assertJsonPath('total_unique', 1)->assertJsonPath('registrations.total', 0);
    }

    public function test_subadmin_requires_explicit_view_permission(): void
    {
        DB::table('users')->insert(['id' => 2, 'email' => 'subadmin@example.com', 'user_role_id' => 2, 'status' => 'active']);
        $this->actingAs(User::find(2), 'admin')->getJson('/individual-subscription-waitlist')->assertForbidden();
        $menu = DB::table('admin_menu')->where('menu_name', 'Individual Subscription Waitlist')->value('id');
        DB::table('permission_users')->insert(['user_id' => 2, 'menu_id' => $menu, 'is_view' => 'no']);
        $this->getJson('/individual-subscription-waitlist')->assertForbidden();
        DB::table('permission_users')->where('user_id', 2)->update(['is_view' => 'yes']);
        $this->getJson('/individual-subscription-waitlist')->assertOk();
    }
}
