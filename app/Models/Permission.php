<?php

namespace App\Models;

class Permission extends \Illuminate\Database\Eloquent\Model
{
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
