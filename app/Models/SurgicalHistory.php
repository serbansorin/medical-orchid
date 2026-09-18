<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SurgicalHistory extends \Illuminate\Database\Eloquent\Model
{
    //

    use SoftDeletes;

    protected $table = 'surgical_history';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'surgery_date' => 'datetime',
        'hospital_days' => 'int',
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
