<?php

namespace Tests\Feature\School;

use App\Jobs\SendSchoolParentCodeMail;
use App\Models\Category;
use App\Models\PermissionUser;
use App\Models\School;
use App\Models\SchoolParentInvite;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserContentWatchHistory;
use App\Models\VideoContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The Phase 1 / Phase 2 close-out: Get Help enforcement, free school entitlement,
 * Add Child removal for independent schools, parent progress, email preview and
 * the "send the school code to parents" tools.
 */
class PhaseCloseoutTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(int $menu, string $modify = 'yes'): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => $menu, 'is_view' => 'yes', 'is_modify' => $modify]);

        return $admin;
    }

    private function independentStudent(School $school): User
    {
        return User::factory()->create(['user_role_id' => 4, 'user_type' => 'child', 'school_id' => $school->id,
            'parent_id' => null, 'status' => 'active', 'username' => 'stu_' . uniqid(), 'dob' => '2013-05']);
    }

    /** The local legacy schema still has a foreign key from watch history to children.id. */
    private function legacyChildRow(User $user, School $school): void
    {
        DB::table('children')->insert(['id' => $user->id, 'name' => 'fixture', 'parent_id' => $school->user_id,
            'dob' => '2013-05-01', 'status' => 'active']);
    }

    // ---- Get Help enforcement -------------------------------------------------

    public function test_get_help_request_is_refused_when_the_school_switched_the_safety_feature_off(): void
    {
        Mail::fake();
        $school = School::factory()->create(['account_mode' => 'independent', 'needs_safety_feature' => 'no']);
        Passport::actingAs($this->independentStudent($school), [], 'api');

        $this->postJson('/api/child-support', ['type' => 'school', 'language' => 'english'])->assertOk()
            ->assertJsonPath('status', false)->assertJsonPath('message', 'Get Help is not enabled for your school.');
    }

    public function test_independent_student_can_send_get_help_to_their_own_school_when_enabled(): void
    {
        Mail::fake();
        $school = School::factory()->create(['account_mode' => 'independent', 'needs_safety_feature' => 'yes']);
        Passport::actingAs($this->independentStudent($school), [], 'api');

        // Used to answer "School not found" because the school was only looked up via a parent.
        $this->postJson('/api/child-support', ['type' => 'school', 'language' => 'english'])->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_independent_student_asking_for_parent_help_gets_a_clean_error_not_a_500(): void
    {
        Mail::fake();
        $school = School::factory()->create(['account_mode' => 'independent', 'needs_safety_feature' => 'yes']);
        Passport::actingAs($this->independentStudent($school), [], 'api');

        $this->postJson('/api/child-support', ['type' => 'parent', 'language' => 'english'])->assertOk()
            ->assertJsonPath('status', false);
    }

    public function test_safety_helper_defaults_to_enabled_outside_a_school_and_follows_the_school_setting(): void
    {
        $this->assertTrue(School::safetyFeatureEnabledFor(User::factory()->parent()->create()));
        $off = School::factory()->create(['needs_safety_feature' => 'no']);
        $parent = User::factory()->parent()->inSchool($off)->create();
        $child = User::factory()->create(['user_role_id' => 4, 'parent_id' => $parent->id, 'school_id' => null, 'status' => 'active']);
        $this->assertFalse(School::safetyFeatureEnabledFor($child));
        $this->assertFalse(School::safetyFeatureEnabledFor($parent));
    }

    // ---- Free access for school members --------------------------------------

    public function test_independent_student_profile_always_has_an_active_subscription(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent', 'status' => 'active']);
        $student = $this->independentStudent($school);
        // A grant that already lapsed must not leave the app with a null subscription.
        Subscription::create(['user_id' => $student->id, 'subscription_type_id' => 'x', 'user_type' => 'parent',
            'subscription_type' => 'monthly', 'start_date' => now()->subMonths(3)->toDateString(),
            'end_date' => now()->subMonth()->toDateString(), 'currency' => 'SGD', 'status' => 'Successful',
            'source' => 'school_grant', 'price' => '0']);
        Passport::actingAs($student, [], 'api');

        $response = $this->getJson('/api/get-profile?language=english')->assertOk();

        $this->assertNotNull($response->json('data.subscription'), 'subscription must not be null for a school member');
        $this->assertTrue(
            Subscription::where('user_id', $student->id)->whereDate('end_date', '>=', now())->exists()
        );
    }

    public function test_individual_parent_keeps_a_null_subscription_when_they_have_none(): void
    {
        Passport::actingAs(User::factory()->parent()->create(), [], 'api');

        $this->getJson('/api/get-profile?language=english')->assertOk()->assertJsonPath('data.subscription', null);
    }

    // ---- Add Child ------------------------------------------------------------

    public function test_parent_of_an_independent_school_cannot_add_a_child(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        Passport::actingAs(User::factory()->parent()->inSchool($school)->create(), [], 'api');

        $this->postJson('/api/add-child', ['name' => 'Kid One', 'username' => 'kid_' . uniqid(), 'dob' => '2013-05',
            'password' => 'Example@123', 'language' => 'english'])
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', "Your school creates your child's account. Please use the login details your school provided.");
        $this->assertDatabaseMissing('users', ['name' => 'Kid One']);
    }

    public function test_profile_tells_the_app_whether_add_child_is_available(): void
    {
        $independent = School::factory()->create(['account_mode' => 'independent']);
        $linked = School::factory()->create(['account_mode' => 'linked']);

        Passport::actingAs(User::factory()->parent()->inSchool($independent)->create(), [], 'api');
        $this->getJson('/api/get-profile?language=english')->assertJsonPath('data.can_add_child', false);

        Passport::actingAs(User::factory()->parent()->inSchool($linked)->create(), [], 'api');
        $this->getJson('/api/get-profile?language=english')->assertJsonPath('data.can_add_child', true);

        Passport::actingAs(User::factory()->parent()->create(), [], 'api');
        $this->getJson('/api/get-profile?language=english')->assertJsonPath('data.can_add_child', true);
    }

    // ---- Parent individual progress ------------------------------------------

    public function test_parent_dashboard_returns_the_parents_own_progress_matching_get_category(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $category = Category::create(['category_name' => 'Body Boost closeout', 'status' => 'active', 'priority' => 1]);
        $video = VideoContent::create(['category_id' => $category->id, 'title' => 'Parent video', 'description' => 'x',
            'user_type' => 'parent', 'video_duration' => '10:00', 'status' => 'active']);
        $this->legacyChildRow($parent, $school);
        UserContentWatchHistory::create(['child_id' => $parent->id, 'video_content_id' => $video->id,
            'last_watched_duration' => '05:00', 'total_video_duration' => '10:00', 'is_completed' => 'no']);
        Passport::actingAs($parent, [], 'api');

        $dashboard = $this->postJson('/api/parent-dashboard', ['language' => 'english'])->assertOk();
        $this->assertSame([], $dashboard->json('data'), 'children list is untouched for the shipped app');
        $own = collect($dashboard->json('individual_progress'))->firstWhere('category_id', $category->id);
        $this->assertSame('Body Boost closeout', $own['category']);
        $this->assertEquals(50, $own['watched_percentage']);

        $apiCategory = collect($this->postJson('/api/get-category', ['language' => 'english'])->json('data'))
            ->firstWhere('id', $category->id);
        $this->assertEquals($apiCategory['watched_percentage'], $own['watched_percentage'], 'dashboard and get-category must agree');
    }

    public function test_admin_parent_details_shows_the_same_progress_and_the_parent_title(): void
    {
        $school = School::factory()->create(['account_mode' => 'independent']);
        $parent = User::factory()->parent()->inSchool($school)->create();
        $category = Category::create(['category_name' => 'Head Strong closeout', 'status' => 'active', 'priority' => 2]);
        $video = VideoContent::create(['category_id' => $category->id, 'title' => 'Parent video 2', 'description' => 'x',
            'user_type' => 'both', 'video_duration' => '10:00', 'status' => 'active']);
        $this->legacyChildRow($parent, $school);
        UserContentWatchHistory::create(['child_id' => $parent->id, 'video_content_id' => $video->id,
            'last_watched_duration' => '00:10', 'total_video_duration' => '10:00', 'is_completed' => 'yes']);

        // A video marked completed counts in full, exactly as the app does.
        $this->actingAs($this->admin(2), 'admin')->get(route('school-user-child-detail', $parent->id))
            ->assertOk()->assertSee('Parent Details')->assertSee('Head Strong closeout')
            ->assertViewHas('data', fn ($data) => $data->categoryProgress[$category->id]['percentage'] == 100);
    }

    // ---- Edit User page -------------------------------------------------------

    public function test_edit_user_page_no_longer_shows_child_progress(): void
    {
        $parent = User::factory()->parent()->create();
        // The parent's own progress card only renders when a category exists.
        Category::create(['category_name' => 'Edit page category', 'status' => 'active', 'priority' => 3]);
        User::factory()->create(['user_role_id' => 4, 'user_type' => 'child', 'parent_id' => $parent->id,
            'status' => 'active', 'dob' => '2013-05', 'username' => 'edit_' . uniqid()]);

        $this->actingAs($this->admin(2), 'admin')->get(route('user.edit', $parent->id))->assertOk()
            ->assertSee('Parent Video Progress')->assertSee('Policy Verification Details')
            ->assertDontSee('Quiz Progress')->assertDontSee('Category Progress');
    }

    // ---- Email preview + test send -------------------------------------------

    public function test_email_template_preview_shows_the_qr_and_sample_values(): void
    {
        $this->actingAs($this->admin(6), 'admin')
            ->get(route('email-template.preview', 'school_student_credentials'))
            ->assertOk()->assertSee('Student accounts are ready for Sample School')
            ->assertSee('data:image/jpeg;base64', false)->assertSee('Sample School');
    }

    public function test_teacher_email_preview_and_test_send_work(): void
    {
        Mail::fake();
        $this->actingAs($this->admin(6), 'admin')
            ->get(route('email-template.preview', 'signup_teacher'))->assertOk()->assertSee('data:image/jpeg;base64', false);

        $this->post(route('email-template.test-send', 'signup_teacher'), ['to' => 'approver@example.test'])
            ->assertRedirect(route('email-template.preview', 'signup_teacher'))->assertSessionHas('success');
    }

    public function test_preview_requires_modify_permission_and_a_valid_address(): void
    {
        $this->actingAs($this->admin(6, 'no'), 'admin')
            ->get(route('email-template.preview', 'signup_teacher'))->assertForbidden();

        $this->actingAs($this->admin(6), 'admin')
            ->post(route('email-template.test-send', 'signup_teacher'), ['to' => 'not-an-email'])
            ->assertSessionHasErrors('to');
    }

    // ---- Send the school code to parents -------------------------------------

    public function test_school_code_email_is_queued_for_valid_unique_addresses(): void
    {
        Queue::fake();
        $school = School::factory()->create(['status' => 'active', 'self_signup_enabled' => 'yes']);

        $this->actingAs($this->admin(3), 'admin')->post(route('school.parent-code.send', $school->id), [
            'parent_emails' => "a@example.test, B@example.test\na@example.test bad-address",
        ])->assertSessionHas('success');

        Queue::assertPushed(SendSchoolParentCodeMail::class, 1);
    }

    public function test_school_code_email_refuses_when_signup_with_the_code_is_off(): void
    {
        Queue::fake();
        $school = School::factory()->create(['status' => 'active', 'self_signup_enabled' => 'no']);

        $this->actingAs($this->admin(3), 'admin')->post(route('school.parent-code.send', $school->id), [
            'parent_emails' => 'a@example.test',
        ])->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    public function test_school_code_email_adds_recipients_to_the_roster_when_it_is_enforced(): void
    {
        Queue::fake();
        $school = School::factory()->enforcingRoster()->create(['status' => 'active', 'self_signup_enabled' => 'yes']);

        $this->actingAs($this->admin(3), 'admin')->post(route('school.parent-code.send', $school->id), [
            'parent_emails' => 'Roster@Example.test',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('school_parent_invites', ['school_id' => $school->id, 'email' => 'roster@example.test', 'status' => 'invited']);
    }

    public function test_scheduled_send_delays_the_jobs(): void
    {
        Queue::fake();
        $school = School::factory()->create(['status' => 'active', 'self_signup_enabled' => 'yes']);

        $this->actingAs($this->admin(3), 'admin')->post(route('school.parent-code.send', $school->id), [
            'parent_emails' => 'a@example.test',
            'send_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertSessionHas('success');

        Queue::assertPushed(SendSchoolParentCodeMail::class, fn ($job) => $job->delay !== null);
    }

    public function test_parent_letter_downloads_as_a_pdf(): void
    {
        $school = School::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin(3), 'admin')->get(route('school.parent-letter', $school->id))->assertOk();

        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_only_modify_permission_can_send_the_code(): void
    {
        Queue::fake();
        $school = School::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin(3, 'no'), 'admin')->post(route('school.parent-code.send', $school->id), [
            'parent_emails' => 'a@example.test',
        ])->assertForbidden();
        Queue::assertNothingPushed();
    }

    // ---- Student credentials re-download -------------------------------------

    public function test_latest_student_credentials_stay_downloadable_from_school_details(): void
    {
        Storage::fake('local');
        $school = School::factory()->create(['account_mode' => 'independent']);
        Storage::disk('local')->put("student-credentials/{$school->id}/" . Str::uuid() . '.xlsx', 'old');
        $newest = (string) Str::uuid();
        Storage::disk('local')->put("student-credentials/{$school->id}/{$newest}.xlsx", 'new');
        touch(Storage::disk('local')->path("student-credentials/{$school->id}/{$newest}.xlsx"), time() + 60);

        $admin = $this->admin(3);
        $this->actingAs($admin, 'admin')->get(route('school.show', $school->id))
            ->assertOk()->assertSee('Download latest student credentials');
        $this->get(route('school.students.credentials.latest', $school->id))->assertOk();
    }

    // ---- Parent sign-up hardening --------------------------------------------

    public function test_self_registration_respects_the_schools_parent_limit(): void
    {
        Mail::fake();
        $school = School::factory()->create(['status' => 'active', 'self_signup_enabled' => 'yes', 'max_limit' => 1]);
        User::factory()->parent()->inSchool($school)->create();

        $this->postJson('/api/register', ['name' => 'Late Parent', 'email' => 'late_' . uniqid() . '@example.test',
            'country_code' => '+65', 'phone_no' => (string) fake()->unique()->numerify('8#######'),
            'password' => 'Example@123', 'user_type' => 'parent', 'terms_n_conditions_accepted' => 'yes',
            'language' => 'english', 'school_code' => $school->school_code])
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'This school has reached its limit for parent accounts. Please contact your school.');
    }

    public function test_registration_requires_a_password(): void
    {
        $this->postJson('/api/register', ['name' => 'No Password', 'email' => 'np_' . uniqid() . '@example.test',
            'country_code' => '+65', 'phone_no' => (string) fake()->unique()->numerify('8#######'), 'language' => 'english'])
            ->assertJsonPath('status', false);
    }

    public function test_an_expired_otp_is_rejected(): void
    {
        $phone = (string) fake()->unique()->numerify('9#######');
        User::factory()->parent()->create(['phone_no' => $phone, 'country_code' => '+65', 'otp' => '1234',
            'otp_expires_at' => now()->subMinute()]);

        $this->postJson('/api/verify-otp', ['otp' => '1234', 'phone_no' => $phone, 'country_code' => '+65', 'type' => 'parent', 'language' => 'english'])
            ->assertJsonPath('status', false)->assertJsonPath('message', 'OTP has expired. Please request a new one.');
    }

    public function test_a_fresh_otp_still_verifies(): void
    {
        $phone = (string) fake()->unique()->numerify('9#######');
        User::factory()->parent()->create(['phone_no' => $phone, 'country_code' => '+65', 'otp' => '4321',
            'otp_expires_at' => now()->addMinutes(3)]);

        $this->postJson('/api/verify-otp', ['otp' => '4321', 'phone_no' => $phone, 'country_code' => '+65', 'type' => 'parent', 'language' => 'english'])
            ->assertJsonPath('status', true);
    }

    // ---- Throttles + webhooks -------------------------------------------------

    public function test_login_is_throttled_per_account_not_per_ip(): void
    {
        $victim = 'throttle_' . uniqid();
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', ['type' => 'child', 'username' => $victim, 'password' => 'wrong', 'language' => 'english'])
                ->assertStatus(200);
        }
        $this->postJson('/api/login', ['type' => 'child', 'username' => $victim, 'password' => 'wrong', 'language' => 'english'])
            ->assertStatus(429);

        // Another student on the same network is unaffected.
        $this->postJson('/api/login', ['type' => 'child', 'username' => 'other_' . uniqid(), 'password' => 'wrong', 'language' => 'english'])
            ->assertStatus(200);
    }

    public function test_store_webhooks_require_the_shared_secret_when_one_is_configured(): void
    {
        $this->postJson('/api/webhooks/google', [])->assertOk();

        config(['services.webhooks.shared_secret' => 's3cret']);
        $this->postJson('/api/webhooks/google', [])->assertStatus(401);
        $this->postJson('/api/webhooks/apple', [])->assertStatus(401);
        $this->postJson('/api/webhooks/google?token=wrong', [])->assertStatus(401);
        $this->postJson('/api/webhooks/google?token=s3cret', [])->assertOk();
        $this->postJson('/api/webhooks/apple', [], ['X-Webhook-Secret' => 's3cret'])->assertOk();
    }
}
