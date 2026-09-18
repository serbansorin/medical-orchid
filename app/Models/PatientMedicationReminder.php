<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientMedicationReminder extends Model
{
    protected $fillable = [
        'patient_id',
        'medication_id',
        'reminder_times',
        'reminder_type',
        'is_enabled',
        'timezone',
        'google_event_ids',
    ];

    protected $casts = [
        'reminder_times' => 'array',
        'is_enabled' => 'boolean',
        'google_event_ids' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PatientMedicationLog::class, 'reminder_id');
    }
}
