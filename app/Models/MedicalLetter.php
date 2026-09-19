<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class MedicalLetter extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'template_id',
        'letter_type',
        'title',
        'content',
        'recommendations',
        'diagnosis',
        'structured_data',
        'letter_date',
        'reference_number',
        'referred_to_doctor',
        'referred_to_clinic',
        'referred_specialty',
        'is_signed',
        'signed_at',
        'signature_path',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'letter_date' => 'datetime',
        'is_signed' => 'bool',
        'signed_at' => 'datetime',
        'created_by' => 'int',
        'updated_by' => 'int',
        'template_id' => 'int',
        'structured_data' => 'array',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function template()
    {
        return $this->belongsTo(MedicalLetterTemplate::class, 'template_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $letter): void {
            if (empty($letter->created_by) && auth()->id()) {
                $letter->created_by = auth()->id();
            }
        });

        static::updating(function (self $letter): void {
            if (auth()->id()) {
                $letter->updated_by = auth()->id();
            }
        });
    }

    protected $table = 'medical_letters';

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
