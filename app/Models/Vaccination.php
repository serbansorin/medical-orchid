<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class Vaccination extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    //

    use SoftDeletes;

    protected $table = 'vaccinations';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'administration_date' => 'datetime',
        'expiration_date' => 'datetime',
        'next_dose_date' => 'datetime',
        'dose_amount' => 'float',
        'administered_by' => 'int',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
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
