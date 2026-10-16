<?php

use Database\Seeders\Emails\VerificationEmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the hand-made production `verification_email` row (older layout,
     * unknown wording) with the branded one. Unlike the insert-only template
     * migrations this deliberately overwrites: the stored copy is the very
     * thing being reported as off-format.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $values = [
            'subject' => VerificationEmailTemplate::SUBJECT,
            'variables' => VerificationEmailTemplate::VARIABLES,
            'description' => VerificationEmailTemplate::html(),
            'active' => 1,
            'updated_at' => now(),
        ];

        $updated = DB::table('email_templates')
            ->where('variable_name', 'verification_email')
            ->where(fn ($q) => $q->where('language', 'english')->orWhereNull('language'))
            ->update($values);

        if ($updated === 0) {
            DB::table('email_templates')->insert($values + [
                'variable_name' => 'verification_email',
                'language' => 'english',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // The previous wording is not recoverable.
    }
};
