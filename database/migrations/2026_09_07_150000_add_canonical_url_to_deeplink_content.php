<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_sessions', 'user_type')) {
                $table->string('user_type')->nullable();
            }
            if (!Schema::hasColumn('knowledge_sessions', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
        });

        Schema::table('video_contents', function (Blueprint $table) {
            if (!Schema::hasColumn('video_contents', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
        });

        $base = rtrim((string) env('DEEPLINK_PUBLIC_BASE_URL', env('APP_URL', 'https://admin.empoweredhealth.asia')), '/');
        if ($base !== '') {
            DB::table('knowledge_sessions')->orderBy('id')->chunkById(200, function ($rows) use ($base) {
                foreach ($rows as $row) {
                    $url = $base . '/d/article/' . $row->id;
                    if (($row->canonical_url ?? null) !== $url) {
                        DB::table('knowledge_sessions')->where('id', $row->id)->update(['canonical_url' => $url]);
                    }
                }
            });
            DB::table('video_contents')->orderBy('id')->chunkById(200, function ($rows) use ($base) {
                foreach ($rows as $row) {
                    $url = $base . '/d/podcast/' . $row->id;
                    if (($row->canonical_url ?? null) !== $url) {
                        DB::table('video_contents')->where('id', $row->id)->update(['canonical_url' => $url]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('knowledge_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_sessions', 'canonical_url')) {
                $table->dropColumn('canonical_url');
            }
        });
        Schema::table('video_contents', function (Blueprint $table) {
            if (Schema::hasColumn('video_contents', 'canonical_url')) {
                $table->dropColumn('canonical_url');
            }
        });
    }
};
