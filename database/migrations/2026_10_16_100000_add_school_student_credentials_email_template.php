<?php

use Database\Seeders\Emails\StudentCredentialsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the editable student-credentials email. Insert-only: re-running (or a
     * later reseed elsewhere) never overwrites wording an admin has changed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $exists = DB::table('email_templates')
            ->where('variable_name', 'school_student_credentials')
            ->where('language', 'english')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('email_templates')->insert([
            'variable_name' => 'school_student_credentials',
            'language' => 'english',
            'subject' => StudentCredentialsTemplate::SUBJECT,
            'variables' => StudentCredentialsTemplate::VARIABLES,
            'description' => StudentCredentialsTemplate::html(),
            'active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('email_templates')->where('variable_name', 'school_student_credentials')->delete();
    }
};
