<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class PatientInsurancePolicy extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'patient_insurance_policies';

    protected $casts = [
        'patient_id' => 'int',
        'provider_id' => 'int',
        'coverage_start' => 'datetime',
        'coverage_end' => 'datetime',
        'is_primary' => 'bool',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function insurance_provider()
    {
        return $this->belongsTo(InsuranceProvider::class, 'provider_id');
    }
}
