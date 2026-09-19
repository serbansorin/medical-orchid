<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class CacheLock extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

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
