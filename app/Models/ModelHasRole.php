<?php

namespace App\Models;

class ModelHasRole extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_has_roles';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'role_id' => 'int',
        'model_id' => 'int',
        'team_id' => 'int',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
