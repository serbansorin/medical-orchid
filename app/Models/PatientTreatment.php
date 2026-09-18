<?php

namespace App\Models;

use App\Enums\PatientTreatmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientTreatment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_diagnostic_id',
        'treatment_id',
        'title',
        'description',
        'notes',
        'documents',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'treatment_id' => 'integer',
        'documents' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => PatientTreatmentStatus::class,
    ];

    // ── Relationships ──

    public function patientDiagnostic(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnostic::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    // ── Scopes ──

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PatientTreatmentStatus::ACTIVE);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PatientTreatmentStatus::COMPLETED);
    }

    public function scopeForDiagnostic(Builder $query, int $diagnosticId): Builder
    {
        return $query->where('patient_diagnostic_id', $diagnosticId);
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->status === PatientTreatmentStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === PatientTreatmentStatus::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === PatientTreatmentStatus::CANCELLED;
    }

    public function complete(): bool
    {
        $this->status = PatientTreatmentStatus::COMPLETED;
        $this->end_date = now()->toDateString();

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = PatientTreatmentStatus::CANCELLED;
        if (! $this->end_date) {
            $this->end_date = now()->toDateString();
        }

        return $this->save();
    }

    public function reactivate(): bool
    {
        $this->status = PatientTreatmentStatus::ACTIVE;
        $this->end_date = null;

        return $this->save();
    }
}
