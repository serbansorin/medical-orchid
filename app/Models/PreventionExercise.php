<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreventionExercise extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'description',
        'instructions',
        'week_number',
        'day_number',
        'order',
        'duration_seconds',
        'rest_seconds',
        'sets',
        'reps',
        'video_url',
        'thumbnail_url',
        'equipment_needed',
        'muscle_groups',
        'calories_burned',
    ];

    protected $casts = [
        'equipment_needed' => 'array',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(PreventionProgram::class, 'program_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PatientExerciseLog::class, 'exercise_id');
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
