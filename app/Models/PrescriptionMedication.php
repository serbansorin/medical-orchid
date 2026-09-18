<?php

namespace App\Models;

class PrescriptionMedication extends \Illuminate\Database\Eloquent\Model
{
    protected $fillable = [
        'prescription_id',
        'medication_id',
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
    ];

    protected $casts = [
        'prescription_id' => 'int',
        'medication_id' => 'int',
        'quantity' => 'int',
        'duration_days' => 'int',
        'start_date' => 'date',
        'end_date' => 'date',
        'take_with_food' => 'bool',
        'take_before_meals' => 'bool',
        'refills_allowed' => 'int',
        'refills_used' => 'int',
        'is_substitutable' => 'bool',
        'order_number' => 'int',
    ];

    /**
     * Căi de administrare disponibile
     */
    public const ROUTES = [
        'oral' => 'Pe gură',
        'sublingual' => 'Sub limbă',
        'topical' => 'Topic (pe piele)',
        'inhalation' => 'Inhalare',
        'injection_im' => 'Injecție intramusculară',
        'injection_iv' => 'Injecție intravenoasă',
        'injection_sc' => 'Injecție subcutanată',
        'rectal' => 'Rectal',
        'vaginal' => 'Vaginal',
        'ophthalmic' => 'Oftalmic (ochi)',
        'otic' => 'Otic (urechi)',
        'nasal' => 'Nazal',
        'transdermal' => 'Transdermal (plasture)',
        'other' => 'Altele',
    ];

    /**
     * Unități de cantitate disponibile
     */
    public const QUANTITY_UNITS = [
        'tablets' => 'comprimate',
        'capsules' => 'capsule',
        'ml' => 'ml',
        'mg' => 'mg',
        'drops' => 'picături',
        'applications' => 'aplicări',
        'patches' => 'plasturi',
        'suppositories' => 'supozitoare',
        'ampules' => 'fiole',
        'sachets' => 'plicuri',
        'units' => 'unități',
    ];

    /**
     * Obține numele medicamentului (din relație sau din câmpul backup)
     */
    public function getMedicationDisplayNameAttribute(): string
    {
        if ($this->medication) {
            $name = $this->medication->name;
            if ($this->medication->strength) {
                $name .= ' '.$this->medication->strength;
            }

            return $name;
        }

        return $this->medication_name ?? 'Medicament necunoscut';
    }

    /**
     * Obține descrierea completă a dozajului
     */
    public function getDosageDescriptionAttribute(): string
    {
        $parts = [];

        $parts[] = $this->dosage;
        $parts[] = $this->frequency;

        if ($this->take_with_food) {
            $parts[] = 'cu mâncare';
        } elseif ($this->take_before_meals) {
            $parts[] = 'înainte de masă';
        }

        if ($this->duration_days) {
            $parts[] = "timp de {$this->duration_days} zile";
        }

        return implode(' • ', array_filter($parts));
    }

    /**
     * Obține calea de administrare în română
     */
    public function getRouteDisplayAttribute(): string
    {
        return self::ROUTES[$this->route] ?? $this->route;
    }

    /**
     * Verifică dacă medicamentul mai poate fi reîncărcat
     */
    public function canBeRefilled(): bool
    {
        return $this->refills_used < $this->refills_allowed;
    }

    /**
     * Verifică dacă tratamentul este activ (în perioada de administrare)
     */
    public function isActive(): bool
    {
        $now = now()->toDateString();

        if ($this->start_date && $now < $this->start_date->toDateString()) {
            return false; // Nu a început încă
        }

        if ($this->end_date && $now > $this->end_date->toDateString()) {
            return false; // S-a terminat
        }

        return true;
    }

    /**
     * Scope pentru medicamente active
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
        })->where(function ($q) {
            $q->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        });
    }

    /**
     * Scope pentru ordonare după poziție
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number', 'asc');
    }

    protected $table = 'prescription_medications';

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }
}
