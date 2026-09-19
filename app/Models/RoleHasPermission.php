<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class RoleHasPermission extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $table = 'role_has_permissions';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'permission_id' => 'int',
        'role_id' => 'int',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
