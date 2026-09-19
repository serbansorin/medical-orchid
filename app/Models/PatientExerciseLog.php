<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientExerciseLog extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'enrolled_program_id',
        'exercise_id',
        'started_at',
        'completed_at',
        'actual_duration_seconds',
        'actual_sets',
        'actual_reps',
        'calories_burned',
        'difficulty_rating',
        'notes',
        'skipped',
        'skip_reason',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function enrolledProgram(): BelongsTo
    {
        return $this->belongsTo(PatientEnrolledProgram::class, 'enrolled_program_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(PreventionExercise::class, 'exercise_id');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at')->where('skipped', false);
    }

    public function scopeSkipped($query)
    {
        return $query->where('skipped', true);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }
}
