<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MedicalHistory extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'reporting_user_id',
        'type',
        'data',
        'notes',
        'origin_type',
        'origin_id',
    ];

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // ── Relationships ──

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    public function doctor()
    {
        return $this->hasOneThrough(Doctor::class, Appointment::class, 'id', 'user_id', 'appointment_id', 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function reportingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_user_id');
    }

    // ── Static helpers for audit logging ──

    /**
     * Create an audit log entry in the medical history timeline.
     */
    public static function log(
        int $patientId,
        string $type,
        string $description,
        ?int $appointmentId = null,
        ?Model $origin = null,
        ?array $data = null,
    ): static {
        return static::create([
            'patient_id' => $patientId,
            'appointment_id' => $appointmentId,
            'reporting_user_id' => auth()->id(),
            'type' => $type,
            'notes' => $description,
            'data' => $data,
            'origin_type' => $origin ? $origin->getMorphClass() : null,
            'origin_id' => $origin?->getKey(),
        ]);
    }

    // ── Display helpers ──

    protected static array $typeLabels = [
        'notes' => 'Notă medicală',
        'document' => 'Document medical',
        'medical_visit' => 'Vizită medicală',
        'patient_exam_result' => 'Rezultat de investigație',
        'appointment_created' => 'Programare creată',
        'appointment_updated' => 'Programare actualizată',
        'prescription_created' => 'Rețetă emisă',
        'report_added' => 'Raport adăugat',
        'report_updated' => 'Raport actualizat',
        'report_deleted' => 'Raport șters',
    ];

    public function getTypeLabelAttribute(): string
    {
        return static::$typeLabels[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }
}
