<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\Role;
use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'permissions',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions'       => 'array',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'id'         => Where::class,
        'name'       => Like::class,
        'email'      => Like::class,
        'updated_at' => WhereDateStartEnd::class,
        'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    /**
     * Request-scoped cache of legacy permission names.
     *
     * @var list<string>|null
     */
    private ?array $legacyPermissionCache = null;

    /**
     * Request-scoped cache of the super-admin flag.
     */
    private ?bool $superAdminCache = null;

    /**
     * Role assignments live in the shared legacy pivot `model_has_roles`
     * (legacy `roles.id` is bigint; Orchid's own `role_users` table is not
     * created — see the no-op migration of the same concern).
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(
            \Orchid\Support\Facades\Dashboard::model(Role::class),
            'model',
            'model_has_roles',
            'model_id',
            'role_id',
            'id',
            'id',
        )->withPivot('team_id');
    }

    /**
     * Determine whether the user holds the legacy super-admin role.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->superAdminCache === null) {
            $this->superAdminCache = $this->roles
                ->contains(static fn (Role $role): bool => $role->getAttribute('name') === 'super_admin');
        }

        return $this->superAdminCache;
    }

    /**
     * Determine if the user has access to the given permission.
     *
     * Bridges Orchid's access layer to the shared legacy RBAC data:
     *  - super-admin role unlocks everything;
     *  - direct grants from `users.permissions` (Orchid-native json) act as patterns;
     *  - permission names reached through `model_has_roles` → `role_has_permissions`
     *    → `permissions.name` are matched with wildcard support.
     */
    public function hasAccess(string $permit, bool $cache = true): bool
    {
        if ($permit === '') {
            return true;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($this->permissionPatterns() as $pattern) {
            if (Str::is($pattern, $permit)) {
                return true;
            }
        }

        return false;
    }

    /**
     * All permission patterns granted to this user, from both direct
     * (users.permissions) and legacy role-based sources.
     *
     * @return list<string>
     */
    protected function permissionPatterns(): array
    {
        if ($this->legacyPermissionCache !== null) {
            return $this->legacyPermissionCache;
        }

        $patterns = [];

        $direct = $this->permissions;

        if (is_array($direct)) {
            foreach ($direct as $key => $value) {
                if (is_string($key) && $value) {
                    $patterns[] = $key;
                } elseif (is_string($value)) {
                    $patterns[] = $value;
                }
            }
        }

        $roleIds = $this->roles->pluck('id')->all();

        if ($roleIds !== []) {
            $patterns = array_merge($patterns, DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->whereIn('role_has_permissions.role_id', $roleIds)
                ->pluck('permissions.name')
                ->all());
        }

        return $this->legacyPermissionCache = array_values(array_unique($patterns));
    }
}
