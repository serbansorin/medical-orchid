<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionnaireTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'schema',
    ];

    protected $casts = [
        'schema' => 'array',
    ];
}
