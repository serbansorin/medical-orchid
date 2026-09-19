<?php

namespace App\Models;

use Orchid\Screen\AsSource;

class PatientPharmacyPreference extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'pharmacy_id',
        'is_preferred',
    ];

    protected $table = 'patient_pharmacy_preferences';

    protected $casts = [
        'patient_id' => 'int',
        'pharmacy_id' => 'int',
        'is_preferred' => 'bool',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
