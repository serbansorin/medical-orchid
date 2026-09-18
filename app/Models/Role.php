<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orchid\Platform\Models\Role as BaseRole;

/**
 * Bridge over the shared legacy Spatie-style `roles` table.
 *
 * Legacy rows carry `name` (not Orchid's `slug`) and live alongside
 * `model_has_roles` / `role_has_permissions`; the legacy application owns
 * this table, so this class only adapts access — it never migrates it.
 */
class Role extends BaseRole
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'guard_name',
        'team_id',
    ];

    /**
     * Legacy roles are keyed by `name`; expose it as Orchid's `slug`.
     */
    public function getSlugAttribute(): string
    {
        return (string) ($this->attributes['slug'] ?? $this->attributes['name'] ?? '');
    }

    /**
     * The users assigned to this role, via the shared `model_has_roles` pivot.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            'model_has_roles',
            'role_id',
            'model_id',
            'id',
            'id',
        )->withPivot('team_id');
    }

    /**
     * Count permissions granted to this role in the legacy schema.
     */
    public function getCountPermissions(): int
    {
        return $this->permissionNames()->count();
    }

    /**
     * Permission names granted to this role (legacy `role_has_permissions`).
     *
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('role_has_permissions.role_id', $this->getKey())
            ->pluck('permissions.name');
    }
}
