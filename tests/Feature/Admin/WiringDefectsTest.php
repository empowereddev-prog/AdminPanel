<?php

namespace Tests\Feature\Admin;

use App\Models\Country;
use App\Models\PermissionUser;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Wiring defects found by the project-wide review.
 *
 * Every one of these failed silently in production - a bounce with no message,
 * a notification with no text, a warning dialog - which is exactly why they
 * survived. These tests exist so they cannot come back unnoticed.
 */
class WiringDefectsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(int $menuId): User
    {
        $admin = User::factory()->create(['user_role_id' => 1, 'user_type' => 'admin', 'status' => 'active']);
        PermissionUser::create(['user_id' => $admin->id, 'menu_id' => $menuId, 'is_view' => 'yes', 'is_modify' => 'yes']);

        return $admin;
    }

    /** Reference data the UI cannot work without must be wired into db:seed. */
    public function test_reference_seeders_are_registered(): void
    {
        $src = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        foreach (['CountrySeeder', 'NotificationTemplateSeeder', 'AdminMenuSeeder'] as $seeder) {
            $this->assertStringContainsString($seeder . '::class', $src, "{$seeder} must run as part of db:seed.");
        }
    }

    /** The truncating seeders must never wipe a populated table. */
    public function test_truncating_seeders_are_guarded(): void
    {
        foreach (['CountrySeeder', 'AdminMenuSeeder'] as $seeder) {
            $src = file_get_contents(database_path('seeders/' . $seeder . '.php'));
            $guard = substr($src, 0, strpos($src, 'FOREIGN_KEY_CHECKS'));

            $this->assertStringContainsString('->exists()', $guard, "{$seeder} truncates without checking the table is empty first.");
        }
    }

    public function test_country_seeder_is_a_noop_when_already_populated(): void
    {
        (new CountrySeeder())->run();
        $before = Country::count();
        $this->assertGreaterThan(0, $before);

        $marker = Country::create(['code' => 'ZZ', 'country' => 'Testland', 'country_code' => '+999']);
        (new CountrySeeder())->run();

        $this->assertNotNull($marker->fresh(), 'Re-running the seeder must not truncate a populated table.');
        $this->assertSame($before + 1, Country::count());
    }

    /** An empty countries table is what made Add User bounce with no message. */
    public function test_add_user_creates_a_parent(): void
    {
        (new CountrySeeder())->run();
        $email = 'newparent' . uniqid() . '@example.test';

        $this->actingAs($this->admin(2), 'admin')
            ->post(route('user.store'), [
                'name' => 'New Parent',
                'email' => $email,
                'code' => '+65',
                'phone_no' => (string) random_int(80000000, 89999999),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => $email, 'user_role_id' => 3, 'user_type' => 'parent']);
    }

    /** The silent bounce: a missing country must surface as an error on `code`. */
    public function test_add_user_without_a_country_reports_an_error_on_code(): void
    {
        $this->actingAs($this->admin(2), 'admin')
            ->post(route('user.store'), [
                'name' => 'No Country',
                'email' => 'nocountry' . uniqid() . '@example.test',
                'phone_no' => (string) random_int(80000000, 89999999),
            ])
            ->assertSessionHasErrors('code');
    }

    /** Every form blade must be able to show a validation failure. */
    public function test_the_layout_renders_a_validation_summary(): void
    {
        $layout = file_get_contents(resource_path('views/layout/headerFooter.blade.php'));

        $this->assertStringContainsString('$errors->any()', $layout);
        $this->assertStringContainsString('$errors->unique()', $layout);
    }

    /** Blank push notifications: the templates were never seeded. */
    public function test_notification_content_is_not_blank(): void
    {
        (new NotificationTemplateSeeder())->run();

        $content = getNotificationContent('video_content', ['title' => 'My Podcast']);

        $this->assertNotSame('', $content['title']);
        $this->assertNotSame('', $content['body']);
        $this->assertStringContainsString('My Podcast', $content['body']);
    }

    /** A token the caller forgot must never reach the user as literal braces. */
    public function test_unsupplied_tokens_are_stripped_not_shown(): void
    {
        (new NotificationTemplateSeeder())->run();

        $content = getNotificationContent('video_content', ['title' => 'Only Title']);

        $this->assertStringNotContainsString('{', $content['body']);
        $this->assertStringNotContainsString('}', $content['body']);
    }

    /** Payment History asked for an `id` its union query never selects. */
    public function test_payment_history_does_not_request_a_missing_column(): void
    {
        $blade = file_get_contents(resource_path('views/admin/payment/history_index.blade.php'));

        $this->assertStringNotContainsString("data: 'id'", $blade);
    }

    /** Routes whose views do not exist are worse than no route at all. */
    public function test_routes_rendering_missing_views_are_gone(): void
    {
        foreach (['features.index', 'features.edit', 'features.update', 'admin.feature.status',
                  'system-log.index', 'systemlog.data', 'admin.active_plan_details'] as $name) {
            $this->assertFalse(
                app('router')->getRoutes()->hasNamedRoute($name),
                "Route {$name} renders a view that does not exist and must not be registered."
            );
        }
    }

    /** The layout owns jQuery; a page reloading it drops every plugin. */
    public function test_no_blade_reloads_jquery_over_the_layout(): void
    {
        // glob() does not recurse on `**`, so walk the tree - otherwise this
        // assertion passes without having looked at anything.
        $offenders = [];
        $scanned = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $file) {
            if ($file->isDir() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $scanned++;
            if (str_contains((string) file_get_contents($file->getPathname()), 'libs/jquery/1.7.1')) {
                $offenders[] = $file->getFilename();
            }
        }

        $this->assertGreaterThan(100, $scanned, 'The scan found almost no blades - the walk is broken.');
        $this->assertSame([], $offenders, 'These blades downgrade the layout jQuery 3.6.0 to 1.7.1.');
    }
}
