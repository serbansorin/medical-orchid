<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'key',
        'icon',
        'route',
        'parent_menu_id',
        'order',
        'is_active',
        'name_translations',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'name_translations' => 'json',
    ];

    public function parentMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_menu_id');
    }

    public function childMenus(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_menu_id')->orderBy('order');
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(MenuRolePermission::class);
    }

    public function canAccess(string $role, string $action = 'read'): bool
    {
        $column = match ($action) {
            'create' => 'can_create',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'export' => 'can_export',
            default => 'can_read',
        };

        return (bool) $this->rolePermissions()
            ->where('role', $role)
            ->where($column, true)
            ->exists();
    }
}
