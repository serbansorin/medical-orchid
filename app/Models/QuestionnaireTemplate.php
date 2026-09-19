<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class QuestionnaireTemplate extends Model
{
    use AsSource;

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
