<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Medical-Orchid: intentionally a no-op migration.
 *
 * Role assignments are read from the shared legacy pivot `model_has_roles`
 * (see App\Models\User::roles()), not from Orchid's own `role_users` table.
 * Creating `role_users` would fail or mislead: the legacy `roles.id` is
 * bigint (not unsignedInteger as Orchid expects) and the legacy app owns
 * the `roles` table; `model_has_roles` already carries the real data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // no-op — see class docblock
    }

    public function down(): void
    {
        // no-op — see class docblock
    }
};
