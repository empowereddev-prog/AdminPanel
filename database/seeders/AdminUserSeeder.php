<?php

namespace Database\Seeders;

use App\Models\AdminMenu;
use App\Models\PermissionUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@empowered.local'],
            [
                'name' => 'Local Admin',
                'password' => Hash::make('Admin@1234'),
                'user_type' => 'admin',
                'user_role_id' => '1',
                'status' => 'active',
                'is_verified' => '1',
                'otp_verified' => true,
                'email_verified_at' => now(),
                'terms_n_conditions_accepted' => 'yes',
                'language' => 'english',
            ]
        );

        $menus = [
            1 => ['Dashboard', 0],
            2 => ['User Management', 0],
            3 => ['School Management', 0],
            4 => ['Role Permission', 0],
            5 => ['Content Management', 0],
            6 => ['Email Template', 5],
            7 => ['Static Content', 5],
            8 => ['FAQ Management', 5],
            9 => ['Testimonial', 5],
            10 => ['Avtar', 0],
            11 => ['Contact Us', 0],
            12 => ['Settings', 0],
            13 => ['Avatar', 0],
            16 => ['Category', 0],
            17 => ['Knowledge Base', 0],
            18 => ['Knowledge Session', 0],
            19 => ['Quiz', 0],
            20 => ['Quiz Category', 0],
            23 => ['Payment Management', 0],
            24 => ['Mood Tracker', 0],
            26 => ['Product Recommendation', 0],
            27 => ['Meet Team', 0],
            35 => ['Color Management', 0],
            36 => ['Notification Template', 0],
            37 => ['Popup Content', 0],
            38 => ['Video Content', 0],
            39 => ['Video Requests', 0],
        ];

        foreach ($menus as $id => [$name, $pid]) {
            AdminMenu::query()->updateOrCreate(
                ['id' => $id],
                [
                    'menu_name' => $name,
                    'id_general_status' => 1,
                    'pid' => $pid,
                ]
            );
        }

        foreach (array_keys($menus) as $menuId) {
            PermissionUser::query()->updateOrCreate(
                [
                    'user_id' => $admin->id,
                    'menu_id' => $menuId,
                ],
                [
                    'is_view' => 'yes',
                    'is_modify' => 'yes',
                ]
            );
        }
    }
}
