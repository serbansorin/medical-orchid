<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class ModelHasPermission extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

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
