<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(
            [
                // Reference data these screens cannot function without: an empty
                // `countries` leaves every country <select> with no options, and an
                // empty `notification_templates` makes every push and in-app
                // notification render with a blank title and body.
                CountrySeeder::class,
                NotificationTemplateSeeder::class,
                AdminMenuSeeder::class,
                AdminUserSeeder::class,
                FeaturesContentSeeder::class,
                StaticContentSeeder::class,
                AdminOtpEmailTemplateSeeder::class,
                SchoolEmailTemplateSeeder::class,
            ]);

    }
}
