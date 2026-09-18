<?php

namespace App\Models;

class Cache extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'value',
        'expiration',
    ];

    protected $table = 'cache';

    protected $primaryKey = 'key';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'expiration' => 'int',
    ];
}
