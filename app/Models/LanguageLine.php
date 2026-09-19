<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class LanguageLine extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'group',
        'key',
        'text',
    ];

    protected $table = 'language_lines';
}
