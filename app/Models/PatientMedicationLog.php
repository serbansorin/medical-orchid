<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientMedicationLog extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'medication_id',
        'reminder_id',
        'scheduled_at',
        'taken_at',
        'status',
        'dosage',
        'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'taken_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function reminder(): BelongsTo
    {
        return $this->belongsTo(PatientMedicationReminder::class, 'reminder_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeTaken($query)
    {
        return $query->where('status', 'taken');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }
}
