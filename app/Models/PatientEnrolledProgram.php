<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientEnrolledProgram extends Model
{
    protected $fillable = [
        'patient_id',
        'program_id',
        'enrolled_at',
        'started_at',
        'completed_at',
        'current_week',
        'current_day',
        'status',
        'total_sessions_completed',
        'streak_days',
        'last_session_at',
        'settings',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'started_at' => 'date',
        'completed_at' => 'date',
        'last_session_at' => 'date',
        'settings' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(PreventionProgram::class, 'program_id');
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(PatientExerciseLog::class, 'enrolled_program_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function getProgressPercentageAttribute(): int
    {
        $totalWeeks = $this->program->duration_weeks ?? 1;

        return min(100, round(($this->current_week / $totalWeeks) * 100));
    }
}
