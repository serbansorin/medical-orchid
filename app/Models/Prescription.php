<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class Prescription extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    /** @use HasFactory<\Database\Factories\PrescriptionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'clinic_id' => 'int',
        'appointment_id' => 'int',
        'issue_date' => 'date',
        'valid_until' => 'date',
        'is_signed' => 'bool',
        'signed_at' => 'datetime',
    ];

    /**
     * Tipuri de rețete disponibile
     */
    public const TYPES = [
        'standard' => 'Standard',
        'compensated' => 'Compensată',
        'narcotic' => 'Stupefiante',
        'otc' => 'Fără rețetă (OTC)',
    ];

    /**
     * Status-uri disponibile
     */
    public const STATUSES = [
        'draft' => 'În lucru',
        'active' => 'Activă',
        'fulfilled' => 'Ridicată',
        'cancelled' => 'Anulată',
        'expired' => 'Expirată',
    ];

    /**
     * Relația cu clinica
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Relația cu programarea
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relația cu diagnosticul pacientului
     */
    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnostic::class, 'patient_diagnostic_id');
    }

    /**
     * Relația cu medicamentele din rețetă (pivot)
     */
    public function prescriptionMedications(): HasMany
    {
        return $this->hasMany(PrescriptionMedication::class)->ordered();
    }

    /**
     * Relația many-to-many cu medicamentele
     */
    public function medications(): BelongsToMany
    {
        return $this->belongsToMany(Medication::class, 'prescription_medications')
            ->withPivot([
                'medication_name',
                'dosage',
                'frequency',
                'route',
                'quantity',
                'quantity_unit',
                'duration_days',
                'start_date',
                'end_date',
                'instructions',
                'take_with_food',
                'take_before_meals',
                'refills_allowed',
                'refills_used',
                'is_substitutable',
                'order_number',
            ])
            ->withTimestamps()
            ->orderByPivot('order_number');
    }

    /**
     * Generează un număr unic pentru rețetă
     */
    public static function generatePrescriptionNumber(): string
    {
        $date = now()->format('Ymd');
        $sequence = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('RX-%s-%05d', $date, $sequence);
    }

    /**
     * Obține tipul rețetei în română
     */
    public function getTypeDisplayAttribute(): string
    {
        return self::TYPES[$this->prescription_type] ?? $this->prescription_type;
    }

    /**
     * Obține status-ul în română
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Verifică dacă rețeta este activă
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
            ($this->valid_until === null || $this->valid_until->isFuture());
    }

    /**
     * Verifică dacă rețeta este expirată
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' ||
            ($this->valid_until && $this->valid_until->isPast());
    }

    /**
     * Marchează rețeta ca semnată
     */
    public function sign(?string $signaturePath = null): void
    {
        $this->update([
            'is_signed' => true,
            'signed_at' => now(),
            'signature_path' => $signaturePath,
            'status' => 'active',
        ]);
    }

    /**
     * Anulează rețeta
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Marchează rețeta ca ridicată de la farmacie
     */
    public function markAsFulfilled(): void
    {
        $this->update(['status' => 'fulfilled']);
    }

    /**
     * Obține numărul total de medicamente din rețetă
     */
    public function getMedicationCountAttribute(): int
    {
        return $this->prescriptionMedications()->count();
    }

    /**
     * Scope pentru rețete active
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            });
    }

    /**
     * Scope pentru rețete ale unui pacient
     */
    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope pentru rețete ale unui doctor
     */
    public function scopeByDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Boot method pentru generarea automată a numărului de rețetă
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prescription) {
            if (empty($prescription->prescription_number)) {
                $prescription->prescription_number = self::generatePrescriptionNumber();
            }

            if (empty($prescription->issue_date)) {
                $prescription->issue_date = now();
            }

            // Valabilitate 30 de zile pentru rețete standard
            if (empty($prescription->valid_until)) {
                $prescription->valid_until = now()->addDays(30);
            }
        });
    }

    protected $table = 'prescriptions';

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescription_fulfillments()
    {
        return $this->hasMany(PrescriptionFulfillment::class);
    }

    public function prescription_medications()
    {
        return $this->hasMany(PrescriptionMedication::class);
    }

    public function prescription_routings()
    {
        return $this->hasMany(PrescriptionRouting::class);
    }
}
