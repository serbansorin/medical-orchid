<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'type',
        'status',
        'processed_rows',
        'total_rows',
        'errors',
        'meta',
        'user_id',
    ];

    protected $casts = [
        'errors' => 'array',
        'meta' => 'array',
    ];
    //
}
