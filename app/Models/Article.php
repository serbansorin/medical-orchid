<?php

namespace App\Models;

class Article extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'title',
        'content',
        'author_id',
        'category',
        'image',
        'status',
        'views',
        'likes',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected $table = 'articles';

    protected $casts = [
        'author_id' => 'int',
        'views' => 'int',
        'likes' => 'int',
        'is_featured' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
