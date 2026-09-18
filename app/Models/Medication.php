<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'name',
        'active_substance',
        'strength',
        'form',
        'manufacturer',
        'atc_code',
        'cod_cim',
        'authorization_holder',
        'therapeutic_action',
        'prescription_type',
        'package_number',
        'package_description',
        'package_volume',
        'package_validity',
        'marker_symbol',
        'data_actualizare',
        'anm_details',
        'prospect_url',
        'rcp_url',
        'ambalaj_url',
        'source',
        'created_by',
    ];

    protected $casts = [
        'data_actualizare' => 'date',
        'anm_details' => 'array',
    ];

    /**
     * Forme de medicamente disponibile
     */
    public const FORMS = [
        'tablet' => 'Comprimat',
        'capsule' => 'Capsulă',
        'liquid' => 'Sirop/Soluție',
        'injection' => 'Injectabil',
        'ointment' => 'Cremă/Unguent',
        'drops' => 'Picături',
        'inhaler' => 'Spray/Inhalator',
        'suppository' => 'Supozitor',
        'patch' => 'Plasture',
        'powder' => 'Pulbere',
        'gel' => 'Gel',
        'other' => 'Altele',
    ];

    /**
     * Relația cu rețetele prin pivot
     */
    public function prescriptionMedications(): HasMany
    {
        return $this->hasMany(PrescriptionMedication::class);
    }

    /**
     * Relația many-to-many cu rețetele
     */
    public function prescriptions(): BelongsToMany
    {
        return $this->belongsToMany(Prescription::class, 'prescription_medications')
            ->withPivot([
                'medication_name',
                'dosage',
                'frequency',
                'route',
                'quantity',
                'quantity_unit',
                'duration_days',
                'start_date',
                'end_date',
                'instructions',
                'take_with_food',
                'take_before_meals',
                'refills_allowed',
                'refills_used',
                'is_substitutable',
                'order_number',
            ])
            ->withTimestamps();
    }

    /**
     * Obține numele complet al medicamentului (nume + concentrație)
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->name;

        if ($this->strength) {
            $name .= ' '.$this->strength;
        }

        return $name;
    }

    /**
     * Obține forma în română
     */
    public function getFormDisplayAttribute(): string
    {
        return self::FORMS[$this->form] ?? $this->form;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Căutare după nume sau substanță activă
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('active_substance', 'LIKE', "%{$term}%")
                ->orWhere('atc_code', 'LIKE', "%{$term}%");
        });
    }

    protected $table = 'medications';

    public function pharmacy_inventories()
    {
        return $this->hasMany(PharmacyInventory::class);
    }
}
