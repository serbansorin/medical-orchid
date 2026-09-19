<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class MedicalCertificate extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'certificate_type',
        'certificate_number',
        'issue_date',
        'valid_from',
        'valid_until',
        'duration_days',
        'diagnosis',
        'recommendations',
        'restrictions',
        'issued_for',
        'purpose',
        'is_signed',
        'signed_at',
        'signature_path',
        'stamp_path',
        'document_path',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'medical_certificates';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'issue_date' => 'datetime',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'duration_days' => 'int',
        'is_signed' => 'bool',
        'signed_at' => 'datetime',
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

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
