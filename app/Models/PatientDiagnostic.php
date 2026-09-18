<?php

namespace App\Models;

use App\Enums\PatientDiagnosticStatus;
use App\Enums\PatientReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientDiagnostic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'doctor_id',
        'main_doctor_id',
        'title',
        'chief_complaint',
        'status',
        'start_date',
        'end_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => PatientDiagnosticStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // ── Relationships ──

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Doctor who made the diagnosis
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Main/primary doctor overseeing the diagnostic
     */
    public function mainDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PatientReport::class);
    }

    public function conditionReports(): HasMany
    {
        return $this->reports()->where('type', PatientReportType::Condition->value);
    }

    /**
     * Get all symptoms associated with this diagnostic
     */
    public function symptoms(): HasMany
    {
        return $this->hasMany(DiagnosticSymptom::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(PatientExamResult::class);
    }

    /**
     * Get all appointments associated with this diagnostic (many-to-many)
     */
    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'patient_diagnostic_appointments')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get all prescriptions associated with this diagnostic
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get all conditions associated with this diagnostic (many-to-many)
     */
    public function conditions(): BelongsToMany
    {
        return $this->belongsToMany(Condition::class, 'patient_diagnostic_conditions')
            ->withTimestamps();
    }

    /**
     * Get all treatments associated with this diagnostic
     */
    public function treatments(): HasMany
    {
        return $this->hasMany(PatientTreatment::class);
    }

    // ── Scopes ──

    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PatientDiagnosticStatus::ACTIVE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PatientDiagnosticStatus::COMPLETED);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('start_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter diagnostics by status
     */
    public function scopeByStatus(Builder $query, PatientDiagnosticStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    // ── Status Transition Helpers ──

    /**
     * Valid status transitions map
     */
    protected static function getValidTransitions(): array
    {
        return [
            PatientDiagnosticStatus::ACTIVE->value => [
                PatientDiagnosticStatus::COMPLETED->value,
                PatientDiagnosticStatus::REFERRED->value,
                PatientDiagnosticStatus::CANCELLED->value,
            ],
            PatientDiagnosticStatus::REFERRED->value => [
                PatientDiagnosticStatus::ACTIVE->value,
                PatientDiagnosticStatus::COMPLETED->value,
                PatientDiagnosticStatus::CANCELLED->value,
            ],
            PatientDiagnosticStatus::COMPLETED->value => [],
            PatientDiagnosticStatus::CANCELLED->value => [
                PatientDiagnosticStatus::ACTIVE->value,
            ],
        ];
    }

    /**
     * Check if transition to new status is allowed
     */
    public function canTransitionTo(PatientDiagnosticStatus $newStatus): bool
    {
        $validTransitions = self::getValidTransitions();
        $currentStatus = $this->status->value;

        return in_array($newStatus->value, $validTransitions[$currentStatus] ?? [], true);
    }

    /**
     * Transition to a new status
     *
     * @throws \InvalidArgumentException if transition is not allowed
     */
    public function transitionTo(PatientDiagnosticStatus $newStatus): bool
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        // Set end date when completing a diagnostic
        if ($newStatus === PatientDiagnosticStatus::COMPLETED && ! $this->end_date) {
            $this->end_date = now()->toDateString();
        }

        $this->status = $newStatus;

        return $this->save();
    }

    /**
     * Get the primary appointment for this diagnostic
     */
    public function getPrimaryAppointment(): ?Appointment
    {
        return $this->appointments()->wherePivot('is_primary', true)->first();
    }

    /**
     * Check if diagnostic is currently active
     */
    public function isActive(): bool
    {
        return $this->status === PatientDiagnosticStatus::ACTIVE;
    }
}
