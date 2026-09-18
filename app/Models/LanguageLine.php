<?php

namespace App\Models;

class LanguageLine extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'group',
        'key',
        'text',
    ];

    protected $table = 'language_lines';
}
