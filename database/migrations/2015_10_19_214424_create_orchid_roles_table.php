<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Medical-Orchid: shared DB — the legacy app owns `roles` (Spatie-style
        // schema: name/guard_name/team_id). Orchid's role storage is bridged to
        // the legacy tables instead; do not recreate or alter them.
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->jsonb('permissions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
