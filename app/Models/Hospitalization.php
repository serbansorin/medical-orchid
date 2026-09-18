<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Hospitalization extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'hospital_name',
        'hospital_department',
        'admission_number',
        'admission_date',
        'discharge_date',
        'length_of_stay',
        'admission_type',
        'admission_reason',
        'admission_diagnosis',
        'discharge_diagnosis',
        'attending_physician',
        'procedures_performed',
        'treatment_provided',
        'discharge_disposition',
        'discharge_instructions',
        'follow_up_plan',
        'complications',
        'discharge_summary_path',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'hospitalizations';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'length_of_stay' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
