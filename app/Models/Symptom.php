<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Symptom extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'severity_scale', 'is_common', 'meta'];

    protected $casts = [
        'severity_scale' => 'array',
        'is_common' => 'boolean',
        'meta' => 'array',
    ];

    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class, 'condition_symptom')
            ->withPivot('severity_weight')
            ->withTimestamps();
    }
}
