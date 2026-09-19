<?php

namespace App\Models;

use App\Enums\AllergySeverity;
use App\Enums\AllergyType;
use Orchid\Screen\AsSource;

class PatientAllergy extends \Illuminate\Database\Eloquent\Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'allergen_name',
        'allergen_type',
        'severity',
        'reaction_description',
        'onset_date',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'patient_id' => 'int',
        'onset_date' => 'datetime',
        'is_active' => 'boolean',
        'allergen_type' => AllergyType::class,
        'severity' => AllergySeverity::class,
    ];

    /**
     * Get the allergen type as an enum.
     */
    public function getAllergenTypeAttribute($value): ?AllergyType
    {
        return $value ? AllergyType::from($value) : null;
    }

    /**
     * Get the severity as an enum.
     */
    public function getSeverityAttribute($value): ?AllergySeverity
    {
        return $value ? AllergySeverity::from($value) : null;
    }

    protected $table = 'patient_allergies';

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
