<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class Referral extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'referring_doctor_id',
        'appointment_id',
        'referred_to_doctor_name',
        'referred_to_doctor_id',
        'referred_to_specialty',
        'referred_to_clinic',
        'referral_date',
        'valid_until',
        'referral_number',
        'reason_for_referral',
        'clinical_information',
        'diagnosis',
        'requested_services',
        'urgency',
        'status',
        'appointment_scheduled_date',
        'appointment_completed_date',
        'feedback_from_specialist',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'referrals';

    protected $casts = [
        'patient_id' => 'int',
        'referring_doctor_id' => 'int',
        'appointment_id' => 'int',
        'referred_to_doctor_id' => 'int',
        'referral_date' => 'datetime',
        'valid_until' => 'datetime',
        'appointment_scheduled_date' => 'datetime',
        'appointment_completed_date' => 'datetime',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'referring_doctor_id');
    }
}
