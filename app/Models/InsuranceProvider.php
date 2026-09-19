<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchid\Screen\AsSource;

class InsuranceProvider extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    use HasFactory;

    protected $table = 'insurance_providers';

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function patient_insurance_policies()
    {
        return $this->hasMany(PatientInsurancePolicy::class, 'provider_id');
    }
}
