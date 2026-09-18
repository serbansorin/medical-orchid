<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Doctor extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\DoctorFactory> */
    use HasFactory;

    /**
     * Get the specialties for this doctor through their user.
     * The specialties are stored on the user via the user_specialties pivot table.
     */
    public function specialties(): BelongsToMany
    {
        // Return the user's specialties relationship if user exists
        if ($this->user) {
            return $this->user->specialties();
        }

        // Return an empty relationship if no user
        return $this->belongsToMany(Specialty::class, 'user_specialties', 'user_id', 'specialty_id')
            ->whereRaw('1 = 0');
    }

    /**
     * Get the organization for this doctor through their clinics.
     * This is used for Filament multi-tenancy support.
     */
    public function organization(): HasOneThrough
    {
        return $this->hasOneThrough(
            Organization::class,
            Clinic::class,
            'id',                           // Foreign key on clinics (the ID)
            'id',                           // Foreign key on organizations
            'id',                           // Local key on doctors
            'organization_id'               // Local key on clinics
        )->whereIn('clinics.id', function ($query) {
            $query->select('clinic_id')
                ->from('clinic_doctor_assignments')
                ->where('doctor_id', $this->id);
        });
    }

    protected $table = 'doctors';

    protected $casts = [
        'user_id' => 'int',
        'experience' => 'int',
        'is_featured' => 'bool',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment_notes()
    {
        return $this->hasMany(AppointmentNote::class, 'documented_by');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function billing_invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_doctor_assignments')
            ->withPivot('id', 'department_id', 'role', 'status', 'start_date', 'end_date')
            ->withTimestamps();
    }

    public function consent_forms()
    {
        return $this->hasMany(ConsentForm::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient_relationships')
            ->withPivot('id', 'relationship_type', 'status', 'start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    public function doctor_schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function hospitalizations()
    {
        return $this->hasMany(Hospitalization::class);
    }

    public function lab_results()
    {
        return $this->hasMany(LabResult::class, 'reviewed_by');
    }

    public function medical_attachments()
    {
        return $this->hasMany(MedicalAttachment::class, 'reviewed_by');
    }

    public function medical_certificates()
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    public function medical_letters()
    {
        return $this->hasMany(MedicalLetter::class);
    }

    public function medical_records()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function patient_conditions()
    {
        return $this->hasMany(PatientCondition::class, 'diagnosed_by');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referring_doctor_id');
    }

    public function surgical_histories()
    {
        return $this->hasMany(SurgicalHistory::class);
    }

    public function treatment_plans()
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }

    public function vital_signs()
    {
        return $this->hasMany(VitalSign::class);
    }
}
