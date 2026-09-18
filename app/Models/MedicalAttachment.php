<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalAttachment extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'attachable_type',
        'attachable_id',
        'document_type',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'document_date',
        'source',
        'is_reviewed',
        'reviewed_by',
        'reviewed_at',
        'notes',
        'uploaded_by',
    ];

    use SoftDeletes;

    protected $table = 'medical_attachments';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'appointment_id' => 'int',
        'attachable_id' => 'int',
        'file_size' => 'int',
        'document_date' => 'datetime',
        'is_reviewed' => 'bool',
        'reviewed_by' => 'int',
        'reviewed_at' => 'datetime',
        'uploaded_by' => 'int',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'reviewed_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
