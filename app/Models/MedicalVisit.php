<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Orchid\Screen\AsSource;

class MedicalVisit extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'medical_visits';

    protected $guarded = ['id'];

    public const TYPES = [
        'consultation' => 'Consultație',
        'follow_up' => 'Control',
        'emergency' => 'Urgență',
        'procedure' => 'Procedură',
        'teleconsult' => 'Teleconsultație',
        'checkup' => 'Evaluare',
        'admission' => 'Internare',
        'discharge' => 'Externare',
        'day_surgery' => 'Chirurgie de zi',
        'observation' => 'Observație',
        'transfer' => 'Transfer',
        'vaccination' => 'Vaccinare',
    ];

    public const STATUSES = [
        'scheduled' => 'Programată',
        'checked_in' => 'Check-in',
        'in_progress' => 'În desfășurare',
        'completed' => 'Finalizată',
        'no_show' => 'Neprezentat',
        'cancelled' => 'Anulată',
    ];

    protected $casts = [
        'patient_id' => 'int',
        'clinic_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'episode_id' => 'int',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    /**
     * Scope all queries to one clinic (tenant boundary).
     */
    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

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

    public function episode(): BelongsTo
    {
        return $this->belongsTo(VisitEpisode::class, 'episode_id');
    }

    public function serviceLines(): HasMany
    {
        return $this->hasMany(VisitServiceLine::class, 'visit_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'medical_visit_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'medical_visit_id');
    }
}
