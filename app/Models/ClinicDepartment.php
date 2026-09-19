<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class ClinicDepartment extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'clinic_departments';

    protected $casts = [
        'clinic_id' => 'int',
        'is_active' => 'bool',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function clinic_doctor_assignments()
    {
        return $this->hasMany(ClinicDoctorAssignment::class, 'department_id');
    }
}
