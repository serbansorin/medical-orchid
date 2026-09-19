<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class ConsentForm extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'consent_type',
        'title',
        'description',
        'risks_explained',
        'benefits_explained',
        'alternatives_explained',
        'consent_date',
        'valid_until',
        'is_signed',
        'signed_at',
        'patient_signature_path',
        'witness_signature_path',
        'witness_name',
        'can_revoke',
        'is_revoked',
        'revoked_at',
        'revocation_reason',
        'document_path',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'consent_forms';

    protected $casts = [
        'patient_id' => 'int',
        'doctor_id' => 'int',
        'consent_date' => 'datetime',
        'valid_until' => 'datetime',
        'is_signed' => 'bool',
        'signed_at' => 'datetime',
        'can_revoke' => 'bool',
        'is_revoked' => 'bool',
        'revoked_at' => 'datetime',
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
