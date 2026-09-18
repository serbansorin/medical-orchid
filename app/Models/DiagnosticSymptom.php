<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DiagnosticSymptom
 *
 * Model for storing symptoms associated with patient diagnostics
 *
 * @property int $id
 * @property int $patient_diagnostic_id
 * @property string $symptom_name
 * @property string $severity
 * @property string|null $duration
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property PatientDiagnostic $diagnostic
 */
class DiagnosticSymptom extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_diagnostic_id',
        'symptom_name',
        'severity',
        'duration',
        'notes',
    ];

    protected $casts = [
        'patient_diagnostic_id' => 'int',
    ];

    /**
     * Severity levels
     */
    public const SEVERITIES = [
        'mild' => 'Mild',
        'moderate' => 'Moderate',
        'severe' => 'Severe',
    ];

    /**
     * Get the diagnostic that owns the symptom
     */
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnostic::class, 'patient_diagnostic_id');
    }

    /**
     * Get severity label
     */
    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }
}
