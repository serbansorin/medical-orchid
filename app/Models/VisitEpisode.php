<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class VisitEpisode extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;
    use SoftDeletes;

    protected $table = 'visit_episodes';

    protected $guarded = ['id'];

    public const TYPES = [
        'outpatient_course' => 'Cură ambulatorie',
        'admission' => 'Internare',
        'day_care' => 'Spitalizare de zi',
        'emergency_stay' => 'Urgență cu observație',
        'telemedicine_course' => 'Cură telemedicină',
    ];

    public const STATUSES = [
        'open' => 'Deschis',
        'in_progress' => 'În desfășurare',
        'discharged' => 'Externat',
        'cancelled' => 'Anulat',
    ];

    protected $casts = [
        'patient_id' => 'int',
        'clinic_id' => 'int',
        'organization_id' => 'int',
        'primary_doctor_id' => 'int',
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
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

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(MedicalVisit::class, 'episode_id');
    }
}
