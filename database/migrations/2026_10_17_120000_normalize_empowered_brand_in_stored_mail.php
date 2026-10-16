<?php

use App\Support\Brand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rewrites "EmpowerEd" / "Empower Ed" to the canonical "EmpowerED" in the
     * stored email templates and in the admin-set sender name. Brand::normalize
     * is case-sensitive and leaves domains, addresses and ordinary uses of the
     * word "empowered" untouched.
     */
    public function up(): void
    {
        $changed = 0;

        if (Schema::hasTable('email_templates')) {
            DB::table('email_templates')->orderBy('id')->get(['id', 'subject', 'description'])
                ->each(function ($row) use (&$changed) {
                    $subject = Brand::normalize($row->subject);
                    $description = Brand::normalize($row->description);

                    if ($subject !== (string) $row->subject || $description !== (string) $row->description) {
                        DB::table('email_templates')->where('id', $row->id)
                            ->update(['subject' => $subject, 'description' => $description]);
                        $changed++;
                    }
                });
        }

        if (Schema::hasTable('settings')) {
            $setting = DB::table('settings')->where('title', 'MAIL_FROM_NAME')->first();

            if ($setting && Brand::normalize($setting->value) !== (string) $setting->value) {
                DB::table('settings')->where('title', 'MAIL_FROM_NAME')
                    ->update(['value' => Brand::normalize($setting->value)]);
                $changed++;
            }
        }

        Log::info('EmpowerED brand normalised in stored mail', ['rows_changed' => $changed]);
    }

    public function down(): void
    {
        // The original variant spellings are not worth restoring.
    }
};
