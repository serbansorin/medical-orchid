<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientMedicationSideEffect extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'medication_id',
        'symptom',
        'severity',
        'description',
        'started_at',
        'ended_at',
        'reported_to_doctor',
        'reported_to_user_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'reported_to_doctor' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function reportedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_to_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeSevere($query)
    {
        return $query->where('severity', 'severe');
    }
}
