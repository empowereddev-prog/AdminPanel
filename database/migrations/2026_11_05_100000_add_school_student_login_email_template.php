<?php

use Database\Seeders\Emails\StudentLoginTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the editable per-student login email. Insert-only: re-running (or a
     * later reseed elsewhere) never overwrites wording an admin has changed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $exists = DB::table('email_templates')
            ->where('variable_name', 'school_student_login')
            ->where('language', 'english')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'variable_name' => 'school_student_login',
            'language' => 'english',
            'subject' => StudentLoginTemplate::SUBJECT,
            'variables' => StudentLoginTemplate::VARIABLES,
            'description' => StudentLoginTemplate::html(),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('variable_name', 'school_student_login')->delete();
    }
};
