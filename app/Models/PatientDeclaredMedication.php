<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientDeclaredMedication extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'name',
        'dosage',
        'frequency',
        'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
