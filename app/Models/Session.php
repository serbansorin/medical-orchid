<?php

namespace App\Models;

class Session extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
    ];

    protected $table = 'sessions';

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'user_id' => 'int',
        'last_activity' => 'int',
    ];
}
