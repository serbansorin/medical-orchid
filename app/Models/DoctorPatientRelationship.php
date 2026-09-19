<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class DoctorPatientRelationship extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'relationship_type',
        'status',
        'start_date',
        'end_date',
        'notes',
    ];

    protected $table = 'doctor_patient_relationships';

    protected $casts = [
        'doctor_id' => 'int',
        'patient_id' => 'int',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
