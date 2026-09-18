<?php

namespace App\Models;

class ModelHasPermission extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'model_has_permissions';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'permission_id' => 'int',
        'model_id' => 'int',
        'team_id' => 'int',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
