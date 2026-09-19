<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Orchid\Screen\AsSource;

class Procedure extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($procedure) {
            if (empty($procedure->uuid)) {
                $procedure->uuid = (string) Str::uuid();
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Codurile de diagnostic asociate procedurii (ICD).
     * Relație many-to-many prin tabelul pivot procedure_diagnosis_codes.
     */
    public function diagnosisCodes(): BelongsToMany
    {
        return $this->belongsToMany(MedicalCode::class, 'procedure_diagnosis_codes')
            ->withPivot(['code_role', 'sequence'])
            ->withTimestamps()
            ->orderBy('procedure_diagnosis_codes.sequence');
    }

    /**
     * Diagnosticul principal al procedurii.
     */
    public function primaryDiagnosis(): BelongsToMany
    {
        return $this->diagnosisCodes()
            ->wherePivot('code_role', 'primary');
    }

    /**
     * Diagnosticele secundare ale procedurii.
     */
    public function secondaryDiagnoses(): BelongsToMany
    {
        return $this->diagnosisCodes()
            ->wherePivot('code_role', 'secondary');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Proceduri pentru un pacient specific.
     */
    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Proceduri efectuate de un medic specific.
     */
    public function scopeByDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Proceduri finalizate.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Proceduri programate (nu încă efectuate).
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereIn('status', ['preparation', 'in_progress']);
    }

    /**
     * Proceduri din categorie specifică.
     */
    public function scopeOfCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Proceduri chirurgicale.
     */
    public function scopeSurgical(Builder $query): Builder
    {
        return $query->where('category', 'surgical');
    }

    /**
     * Proceduri diagnostice.
     */
    public function scopeDiagnostic(Builder $query): Builder
    {
        return $query->where('category', 'diagnostic');
    }

    /**
     * Proceduri efectuate în interval de date.
     */
    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Proceduri facturabile.
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('is_billable', true);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get status label in Romanian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'preparation' => 'În pregătire',
            'in_progress' => 'În desfășurare',
            'not_done' => 'Nu s-a efectuat',
            'on_hold' => 'Suspendat',
            'stopped' => 'Oprit',
            'completed' => 'Finalizat',
            'entered_in_error' => 'Eroare de intrare',
            'unknown' => 'Necunoscut',
            default => $this->status,
        };
    }

    /**
     * Get category label in Romanian.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'surgical' => 'Chirurgical',
            'diagnostic' => 'Diagnostic',
            'therapeutic' => 'Terapeutic',
            'preventive' => 'Preventiv',
            'imaging' => 'Imagistică',
            'laboratory' => 'Laborator',
            'counseling' => 'Consiliere',
            'other' => 'Altele',
            default => $this->category,
        };
    }

    /**
     * Get priority label in Romanian.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'routine' => 'De rutină',
            'urgent' => 'Urgent',
            'asap' => 'Cât mai curând',
            'stat' => 'Imediat',
            default => $this->priority,
        };
    }

    /**
     * Get outcome label in Romanian.
     */
    public function getOutcomeLabelAttribute(): ?string
    {
        return match ($this->outcome) {
            'successful' => 'Succes',
            'partially_successful' => 'Succes parțial',
            'unsuccessful' => 'Nereușit',
            'complicated' => 'Cu complicații',
            'unknown' => 'Necunoscut',
            default => $this->outcome,
        };
    }

    /**
     * Get full procedure code (CPT:12345 format).
     */
    public function getFullCodeAttribute(): ?string
    {
        if ($this->standard_code) {
            return $this->standard_code;
        }

        if ($this->procedureCode) {
            return "CPT:{$this->procedureCode->code}";
        }

        if ($this->procedure_code) {
            return "CPT:{$this->procedure_code}";
        }

        return null;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Mark procedure as completed.
     */
    public function markAsCompleted(?string $outcome = 'successful', ?string $notes = null): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'outcome' => $outcome,
            'post_procedure_notes' => $notes,
        ]);

        return $this;
    }

    /**
     * Calculate duration if start and end times are set.
     */
    public function calculateDuration(): ?int
    {
        if ($this->performed_at && $this->completed_at) {
            return $this->performed_at->diffInMinutes($this->completed_at);
        }

        return $this->duration_minutes;
    }

    /**
     * Check if procedure is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if procedure is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Get formatted diagnoses string (ICD format like OpenEMR).
     * Example: "ICD9:250.00;ICD9:401.9"
     */
    public function getFormattedDiagnoses(): string
    {
        return $this->diagnosisCodes
            ->map(fn ($code) => "{$code->code_system}:{$code->code}")
            ->implode(';');
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Get available status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            'preparation' => 'În pregătire',
            'in_progress' => 'În desfășurare',
            'not_done' => 'Nu s-a efectuat',
            'on_hold' => 'Suspendat',
            'stopped' => 'Oprit',
            'completed' => 'Finalizat',
            'entered_in_error' => 'Eroare de intrare',
            'unknown' => 'Necunoscut',
        ];
    }

    /**
     * Get available category options.
     */
    public static function getCategoryOptions(): array
    {
        return [
            'surgical' => 'Chirurgical',
            'diagnostic' => 'Diagnostic',
            'therapeutic' => 'Terapeutic',
            'preventive' => 'Preventiv',
            'imaging' => 'Imagistică',
            'laboratory' => 'Laborator',
            'counseling' => 'Consiliere',
            'other' => 'Altele',
        ];
    }

    /**
     * Get available priority options.
     */
    public static function getPriorityOptions(): array
    {
        return [
            'routine' => 'De rutină',
            'urgent' => 'Urgent',
            'asap' => 'Cât mai curând posibil',
            'stat' => 'Imediat',
        ];
    }

    /**
     * Get available outcome options.
     */
    public static function getOutcomeOptions(): array
    {
        return [
            'successful' => 'Succes',
            'partially_successful' => 'Succes parțial',
            'unsuccessful' => 'Nereușit',
            'complicated' => 'Cu complicații',
            'unknown' => 'Necunoscut',
        ];
    }

    use SoftDeletes;

    protected $table = 'procedures';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'clinic_id' => 'int',
        'procedure_code_id' => 'int',
        'scheduled_at' => 'datetime',
        'performed_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'int',
        'performed_by' => 'int',
        'anesthesiologist_id' => 'int',
        'devices_used' => 'array',
        'fee' => 'float',
        'is_billable' => 'bool',
        'billing_invoice_id' => 'int',
        'report_signed' => 'bool',
        'report_signed_at' => 'datetime',
        'consent_form_id' => 'int',
        'consent_obtained' => 'bool',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    protected $fillable = [
        'uuid',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'clinic_id',
        'procedure_name',
        'procedure_description',
        'procedure_code_id',
        'procedure_code',
        'standard_code',
        'body_site',
        'laterality',
        'specimen_type',
        'scheduled_at',
        'performed_at',
        'completed_at',
        'duration_minutes',
        'status',
        'status_reason',
        'category',
        'priority',
        'performed_by',
        'performing_facility',
        'performing_department',
        'anesthesia_type',
        'anesthesiologist_id',
        'outcome',
        'outcome_description',
        'complications',
        'follow_up_instructions',
        'devices_used',
        'implants',
        'reason_for_procedure',
        'pre_procedure_notes',
        'intra_procedure_notes',
        'post_procedure_notes',
        'notes',
        'fee',
        'is_billable',
        'billing_invoice_id',
        'report',
        'report_path',
        'report_signed',
        'report_signed_at',
        'consent_form_id',
        'consent_obtained',
        'created_by',
        'updated_by',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function procedureCode(): BelongsTo
    {
        return $this->belongsTo(MedicalCode::class, 'procedure_code_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function anesthesiologist(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function billingInvoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class);
    }

    public function consentForm(): BelongsTo
    {
        return $this->belongsTo(ConsentForm::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
