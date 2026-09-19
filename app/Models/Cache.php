<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class Cache extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

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
