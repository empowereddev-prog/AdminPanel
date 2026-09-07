<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $templates = [
            [
                'subject' => 'Mood Updated',
                'variable_name' => 'mood_update',
                'description' => 'Your child mood has been updated to: {mood_name} with {points} points.',
                'variables' => '{mood_name},{points}',
            ],
            [
                'subject' => 'New Question Added',
                'variable_name' => 'add_question',
                'description' => 'A new question "{question}" has been added in category {category}.',
                'variables' => '{question},{category}',
            ],
            [
                'subject' => 'New Video Uploaded',
                'variable_name' => 'video_content',
                'description' => 'Progress Looks Good on You! New video uploaded: "{title}". Watch here: {video_link}',
                'variables' => '{title},{video_link},{thumbnail}',
            ],
            [
                'subject' => 'New Child Added',
                'variable_name' => 'child_add',
                'description' => 'Your child "{child_name}" add successfully done!',
                'variables' => '{child_name},{battery_points}',
            ],
            [
                'subject' => 'New Article Added',
                'variable_name' => 'add_article',
                'description' => 'A new article "{title}" has been added successfully.',
                'variables' => '{title}',
            ],
            [
                'subject' => 'New Product Added',
                'variable_name' => 'add_product',
                'description' => 'A new product "{title}" has been added successfully.',
                'variables' => '{title}',
            ],
            [
                'subject' => 'Battery Low',
                'variable_name' => 'battery_low',
                'description' => 'Your battery is at {battery_points}%. Log a mood or complete quizzes to top up.',
                'variables' => '{battery_points}',
            ],
        ];
        
        foreach ($templates as $tpl) {
            NotificationTemplate::updateOrCreate(
                ['variable_name' => $tpl['variable_name']],
                $tpl
            );
        }
    }
}
