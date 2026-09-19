<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class MenuRolePermission extends Model
{
    use AsSource;

    protected $fillable = [
        'menu_id',
        'role',
        'can_read',
        'can_create',
        'can_edit',
        'can_delete',
        'can_export',
    ];

    protected $casts = [
        'can_read' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_export' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
