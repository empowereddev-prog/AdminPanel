<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
class AdminMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('admin_menu')->truncate();
        $data =array(
        array('id' => '1','menu_name' => 'Dashboard','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '2','menu_name' => 'User Management','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        // array('id' => '3','menu_name' => 'Parent','id_general_status' => '1','pid' => '2','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        // array('id' => '4','menu_name' => 'Child','id_general_status' => '1','pid' => '2','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '3','menu_name' => 'School Management','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '4','menu_name' => 'Role Permission','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),

        array('id' => '5','menu_name' => 'Content Management','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '6','menu_name' => 'Email Template','id_general_status' => '1','pid' => '5','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '7','menu_name' => 'Static Content','id_general_status' => '1','pid' => '5','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),
        array('id' => '8','menu_name' => 'FAQ Management','id_general_status' => '1','pid' => '5','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),
        array('id' => '9','menu_name' => 'Testimonial','id_general_status' => '1','pid' => '5','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),
        array('id' => '10','menu_name' => 'Avtar','id_general_status' => '1','pid' => '0','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),
        array('id' => '11','menu_name' => 'Contact Us','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '12','menu_name' => 'Settings','id_general_status' => '1','pid' => '0','created_at' => '2023-03-23 13:40:56','updated_at' => '2023-03-23 13:41:03'),
        array('id' => '13','menu_name' => 'App Analytics','id_general_status' => '1','pid' => '0','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),
        array('id' => '14','menu_name' => 'Word Count Analytics','id_general_status' => '1','pid' => '0','created_at' => '2023-05-24 13:40:56','updated_at' => '2023-05-24 13:41:03'),

    


      );
      DB::table('admin_menu')->insert($data);
      DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
