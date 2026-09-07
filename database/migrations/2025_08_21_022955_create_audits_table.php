<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        if (Schema::connection($connection)->hasTable($table)) {
            return;
        }

        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $table->bigIncrements('id');

            // morphs for user (nullable in case of system events or unauthenticated users)
            $table->nullableMorphs('user');

            $table->string('event');
            $table->morphs('auditable');

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->dropIfExists($table);
    }
};
