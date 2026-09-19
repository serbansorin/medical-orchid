<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class ModelHasRole extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

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
