<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCondition extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'patient_id',
        'condition_name',
        'icd_code',
        'icd_code_id', // FK to medical_codes table
        'severity',
        'onset_date',
        'status',
        'notes',
        'diagnosed_by',
    ];

    /**
     * Relația cu codul ICD din tabelul medical_codes.
     */
    public function icdCodeRef(): BelongsTo
    {
        return $this->belongsTo(MedicalCode::class, 'icd_code_id');
    }

    /**
     * Get formatted ICD code string.
     * Returnează codul din relația medical_codes sau din câmpul icd_code.
     */
    public function getIcdCodeDisplayAttribute(): ?string
    {
        if ($this->icdCodeRef) {
            return "{$this->icdCodeRef->code_system}:{$this->icdCodeRef->code} - {$this->icdCodeRef->code_text_short}";
        }

        return $this->icd_code;
    }

    protected $table = 'patient_conditions';

    protected $casts = [
        'patient_id' => 'int',
        'onset_date' => 'datetime',
        'diagnosed_by' => 'int',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'diagnosed_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatment_plans()
    {
        return $this->hasMany(TreatmentPlan::class, 'condition_id');
    }
}
