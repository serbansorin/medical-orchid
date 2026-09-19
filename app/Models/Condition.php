<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Orchid\Screen\AsSource;

class Condition extends Model
{
    use AsSource;

    protected $fillable = ['name', 'slug', 'category', 'description', 'icd_code', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'condition_symptom')
            ->withPivot('severity_weight')
            ->withTimestamps();
    }

    public function pathways(): BelongsToMany
    {
        return $this->belongsToMany(Pathway::class, 'condition_pathway')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function medicalCode(): BelongsTo
    {
        return $this->belongsTo(MedicalCode::class, 'icd_code', 'code');
    }

    public function treatmentPlans()
    {
        return $this->hasMany(PatientTreatmentPlan::class);
    }
}
