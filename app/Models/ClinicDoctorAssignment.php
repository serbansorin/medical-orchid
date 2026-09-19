<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class ClinicDoctorAssignment extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'clinic_doctor_assignments';

    protected $casts = [
        'clinic_id' => 'int',
        'doctor_id' => 'int',
        'department_id' => 'int',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function clinic_department()
    {
        return $this->belongsTo(ClinicDepartment::class, 'department_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
