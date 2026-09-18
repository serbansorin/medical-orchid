<?php

namespace App\Models;

class PatientHealthProfile extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'patient_id',
        'height',
        'weight',
        'blood_type',
        'gender',
        'date_of_birth',
        'emergency_contact_name',
        'emergency_contact_phone',
        'primary_language',
        'insurance_provider',
        'insurance_member_id',
    ];

    protected $table = 'patient_health_profiles';

    protected $casts = [
        'patient_id' => 'int',
        'height' => 'float',
        'weight' => 'float',
        'date_of_birth' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
