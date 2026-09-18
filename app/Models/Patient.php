<?php

namespace App\Models;

use App\Enums\PatientExamType;
use App\Enums\PatientReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'county',
        'postal_code',
        'cnp',
        'emergency_contact_name',
        'emergency_contact_phone',
        'patient_details',
        'settings',
    ];

    protected $casts = [
        'user_id' => 'int',
        'date_of_birth' => 'datetime',
        'patient_details' => 'array',
        'settings' => 'array',
    ];

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_patient');
    }

    /**
     * Medicamentele unice prescrise acestui pacient (din prescriptions + prescription_medications).
     */
    public function prescribedMedications(): Builder
    {
        return Medication::query()
            ->whereHas('prescriptions', fn ($q) => $q->where('patient_id', $this->id))
            ->distinct();
    }

    /**
     * Get the patient's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Get user or email for notifications.
     */
    public function getUserOrEmail(): User|string
    {
        return $this->user ?? $this->email;
    }

    /**
     * Check if patient has a user account.
     */
    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    // ── Patient Diagnostics (Episode of Care) ──

    public function diagnostics(): HasMany
    {
        return $this->hasMany(PatientDiagnostic::class);
    }

    public function activeDiagnostics(): HasMany
    {
        return $this->diagnostics()->active();
    }

    // ── Medical Histories (audit log + timeline) ──

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    // ── Other relationships ──

    public function medicationReminders(): HasMany
    {
        return $this->hasMany(PatientMedicationReminder::class);
    }

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(PatientMedicationLog::class);
    }

    public function medicationSideEffects(): HasMany
    {
        return $this->hasMany(PatientMedicationSideEffect::class);
    }

    public function preferredPharmacies(): HasMany
    {
        return $this->hasMany(PatientPreferredPharmacy::class);
    }

    public function defaultPharmacy(): HasOne
    {
        return $this->hasOne(PatientPreferredPharmacy::class)->where('is_default', true);
    }

    public function enrolledPrograms(): HasMany
    {
        return $this->hasMany(PatientEnrolledProgram::class);
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(PatientExerciseLog::class);
    }

    public function declaredMedications(): HasMany
    {
        return $this->hasMany(PatientDeclaredMedication::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->billing_invoices();
    }

    // ── Vital Signs via PatientReports ──

    /**
     * Get vital signs reports for this patient (type = VitalSign)
     */
    public function vitalSigns()
    {
        return $this->reports()->ofType(PatientReportType::VitalSign);
    }

    public function vital_signs(): HasMany
    {
        return $this->vitalSigns();
    }

    /**
     * Get all patient reports
     */
    public function reports()
    {
        return $this->hasMany(PatientReport::class);
    }

    // ── Deprecated relationships (data moved to patient_reports) ──

    public function patient_conditions(): HasMany
    {
        return $this->reports()->ofType(PatientReportType::Condition);
    }

    public function patient_allergies(): HasMany
    {
        return $this->reports()->ofType(PatientReportType::Allergy);
    }

    /**
     * Alias for patient_allergies() - used by PatientInfolist
     */
    public function allergies(): HasMany
    {
        return $this->patient_allergies();
    }

    /**
     * Alias for patient_conditions() - used by PatientInfolist
     */
    public function conditions(): HasMany
    {
        return $this->patient_conditions();
    }

    public function vaccinations(): HasMany
    {
        return $this->reports()->ofType(PatientReportType::Vaccination);
    }

    public function family_histories(): HasMany
    {
        return $this->reports()->ofType(PatientReportType::FamilyHistory);
    }

    public function familyHistories(): HasMany
    {
        return $this->family_histories();
    }

    public function surgical_histories(): HasMany
    {
        return $this->reports()->ofType(PatientReportType::SurgicalHistory);
    }

    public function surgicalHistories(): HasMany
    {
        return $this->surgical_histories();
    }

    public function lab_results(): HasMany
    {
        return $this->hasMany(PatientExamResult::class)->whereIn('exam_type', [
            PatientExamType::LAB_PANEL->value,
            PatientExamType::BLOOD_TEST->value,
            PatientExamType::URINE_TEST->value,
        ]);
    }

    protected $table = 'patients';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function billing_invoices()
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function consent_forms()
    {
        return $this->hasMany(ConsentForm::class);
    }

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_patient_relationships')
            ->withPivot('id', 'relationship_type', 'status', 'start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    public function emergency_contacts()
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function hospitalizations()
    {
        return $this->hasMany(Hospitalization::class);
    }

    public function medical_attachments()
    {
        return $this->hasMany(MedicalAttachment::class);
    }

    public function medical_certificates()
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    public function medical_letters()
    {
        return $this->hasMany(MedicalLetter::class);
    }

    public function medical_visits()
    {
        return $this->hasMany(Appointment::class)
            ->where('status', 'completed');
    }

    public function patient_health_profile()
    {
        return $this->hasOne(PatientHealthProfile::class);
    }

    public function patient_insurances()
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function patient_insurance_policies()
    {
        return $this->hasMany(PatientInsurancePolicy::class);
    }

    public function pharmacies()
    {
        return $this->belongsToMany(Pharmacy::class, 'patient_pharmacy_preferences')
            ->withPivot('id', 'is_preferred')
            ->withTimestamps();
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }
}
