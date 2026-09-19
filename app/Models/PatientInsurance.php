<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Orchid\Screen\AsSource;

class PatientInsurance extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'insurance_type',
        'insurance_company',
        'policy_number',
        'group_number',
        'cnp',
        'subscriber_name',
        'relationship_to_subscriber',
        'effective_date',
        'expiration_date',
        'status',
        'coverage_details',
        'exclusions',
        'insurance_card_front',
        'insurance_card_back',
        'notes',
        'created_by',
        'updated_by',
    ];

    use SoftDeletes;

    protected $table = 'patient_insurance';

    protected $casts = [
        'patient_id' => 'int',
        'effective_date' => 'datetime',
        'expiration_date' => 'datetime',
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
