<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class PreventionProgram extends Model
{
    use AsSource;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'difficulty_level',
        'duration_weeks',
        'sessions_per_week',
        'image_url',
        'is_active',
        'is_featured',
        'target_conditions',
        'estimated_calories_per_session',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'target_conditions' => 'array',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(PreventionExercise::class, 'program_id');
    }

    public function enrolledPatients(): HasMany
    {
        return $this->hasMany(PatientEnrolledProgram::class, 'program_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
    }
}
