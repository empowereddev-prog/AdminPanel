<?php

use Database\Seeders\Emails\ParentInvitedTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the app QR marker to the parent invitation. The layout has no
     * </body>, so without the marker the QR block would land outside the card.
     * Overwrites this one row (it is gated from the admin panel, so it carries
     * no admin edits); the token list is unchanged.
     */
    public function up(): void
    {
        if (!Schema::hasTable('email_templates')) {
            return;
        }

        $values = [
            'subject' => ParentInvitedTemplate::SUBJECT,
            'variables' => ParentInvitedTemplate::VARIABLES,
            'description' => ParentInvitedTemplate::html(),
            'active' => 1,
            'updated_at' => now(),
        ];

        $updated = DB::table('email_templates')
            ->where('variable_name', 'school_parent_invited')
            ->where('language', 'english')
            ->update($values);

        if ($updated === 0) {
            DB::table('email_templates')->insert($values + [
                'variable_name' => 'school_parent_invited',
                'language' => 'english',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Prior content cannot be reconstructed safely on rollback.
    }
};
