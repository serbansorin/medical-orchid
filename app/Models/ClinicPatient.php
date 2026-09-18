<?php

namespace App\Models;

class ClinicPatient extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'clinic_id',
        'patient_id',
    ];

    protected $table = 'clinic_patient';

    protected $casts = [
        'clinic_id' => 'int',
        'patient_id' => 'int',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
