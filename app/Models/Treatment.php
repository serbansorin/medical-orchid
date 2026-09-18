<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'protocol',
        'medications',
        'duration',
        'meta',
    ];

    protected $casts = [
        'protocol' => 'array',
        'medications' => 'array',
        'duration' => 'array',
        'meta' => 'array',
    ];

    public function pathways(): BelongsToMany
    {
        return $this->belongsToMany(Pathway::class, 'pathway_treatment')
            ->withPivot('order', 'settings')
            ->withTimestamps()
            ->orderByPivot('order');
    }

    /**
     * Get all patient treatments associated with this treatment template
     */
    public function patientTreatments(): HasMany
    {
        return $this->hasMany(PatientTreatment::class);
    }
}
