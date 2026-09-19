<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientPreferredPharmacy extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'pharmacy_id',
        'is_default',
        'priority',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
