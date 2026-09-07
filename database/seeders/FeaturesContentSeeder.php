<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Feature;

class FeaturesContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $langs = ['english', 'simplified_chinese', 'traditional_chinese'];
        $title = ['Work your way', 'Online and offline', 'Format notes'];

        for ($i = 1; $i <= 3; $i++) {
            $key = Str::slug("feature ".$title[$i - 1]);

            foreach ($langs as $lang) {
                // Check if the feature with the specific key and language already exists
                if (!Feature::where("key", $key)->where("language", $lang)->exists()) {
                    Feature::create([
                        "key" => $key,
                        "language" => $lang,
                        "title" => $title[$i - 1],
                        "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
                    ]);
                }
            }
        }
    }
}
