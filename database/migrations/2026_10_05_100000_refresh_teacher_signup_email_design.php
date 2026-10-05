<?php

use Database\Seeders\Emails\TeacherSignupTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        // Update only the English teacher body; leave other templates and subjects intact.
        DB::table('email_templates')
            ->where('variable_name', 'signup_teacher')
            ->where('language', 'english')
            ->update(['description' => TeacherSignupTemplate::html()]);
    }

    public function down(): void
    {
        // Prior admin-edited content cannot be reconstructed safely on rollback.
    }
};
