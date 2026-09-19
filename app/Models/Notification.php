<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class Notification extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

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
