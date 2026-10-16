<?php

use Database\Seeders\Emails\SchoolOnboardedTemplate;
use Database\Seeders\Emails\SchoolUserSignupTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revamps the two school-registration emails. Overwrites those two rows
     * only (any admin edits to their wording are replaced); token lists are
     * unchanged, so the call sites need no change.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $templates = [
            'school_onboarded' => SchoolOnboardedTemplate::class,
            'signup_school_user' => SchoolUserSignupTemplate::class,
        ];

        foreach ($templates as $variableName => $template) {
            $values = [
                'subject' => $template::SUBJECT,
                'variables' => $template::VARIABLES,
                'description' => $template::html(),
                'active' => 1,
                'updated_at' => now(),
            ];

            $updated = DB::table('email_templates')
                ->where('variable_name', $variableName)
                ->where('language', 'english')
                ->update($values);

            if ($updated === 0) {
                DB::table('email_templates')->insert($values + [
                    'variable_name' => $variableName,
                    'language' => 'english',
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Prior admin-edited content cannot be reconstructed safely on rollback.
    }
};
