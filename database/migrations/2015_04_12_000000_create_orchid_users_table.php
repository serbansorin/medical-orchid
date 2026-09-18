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
        // Medical-Orchid: shared DB — add the column only if missing
        // (purely additive; legacy users table is otherwise untouched).
        if (Schema::hasColumn('users', 'permissions')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->jsonb('permissions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['permissions']);
        });
    }
};
