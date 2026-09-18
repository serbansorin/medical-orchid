<?php

namespace App\Models;

class Notification extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $table = 'notifications';

    public $incrementing = false;

    protected $casts = [
        'notifiable_id' => 'int',
        'read_at' => 'datetime',
    ];
}
