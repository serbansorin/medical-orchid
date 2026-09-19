<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class Permission extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected $table = 'permissions';

    public function model_has_permissions()
    {
        return $this->hasMany(ModelHasPermission::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }
}
