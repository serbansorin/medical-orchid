<?php

namespace App\Models;

class Job extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
    ];

    protected $table = 'jobs';

    public $timestamps = false;

    protected $casts = [
        'attempts' => 'int',
        'reserved_at' => 'int',
        'available_at' => 'int',
    ];
}
