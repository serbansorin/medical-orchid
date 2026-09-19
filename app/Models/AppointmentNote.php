<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class AppointmentNote extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'appointment_id',
        'chief_complaint',
        'clinical_findings',
        'assessment',
        'diagnosis',
        'treatment_plan',
        'prescribed_tests',
        'follow_up_required',
        'follow_up_days',
        'notes',
        'documented_by',
    ];

    protected $table = 'appointment_notes';

    protected $casts = [
        'appointment_id' => 'int',
        'follow_up_required' => 'bool',
        'follow_up_days' => 'int',
        'documented_by' => 'int',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'documented_by');
    }
}
