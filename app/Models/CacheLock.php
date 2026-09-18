<?php

namespace App\Models;

class CacheLock extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'owner',
        'expiration',
    ];

    protected $table = 'cache_locks';

    protected $primaryKey = 'key';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'expiration' => 'int',
    ];
}
