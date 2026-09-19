<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class EmergencyContact extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'full_name',
        'relationship',
        'phone_primary',
        'phone_secondary',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'priority_order',
        'is_primary',
        'can_make_medical_decisions',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'emergency_contacts';

    protected $casts = [
        'patient_id' => 'int',
        'priority_order' => 'int',
        'is_primary' => 'bool',
        'can_make_medical_decisions' => 'bool',
        'created_by' => 'int',
        'updated_by' => 'int',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
