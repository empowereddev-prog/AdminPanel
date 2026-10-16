<?php

use Database\Seeders\Emails\ParentSchoolCodeTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Insert-only, so wording an admin has edited is never overwritten. */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $exists = DB::table('email_templates')
            ->where('variable_name', 'school_parent_code')
            ->where('language', 'english')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'variable_name' => 'school_parent_code',
            'language' => 'english',
            'subject' => ParentSchoolCodeTemplate::SUBJECT,
            'variables' => ParentSchoolCodeTemplate::VARIABLES,
            'description' => ParentSchoolCodeTemplate::html(),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('variable_name', 'school_parent_code')->delete();
    }
};
