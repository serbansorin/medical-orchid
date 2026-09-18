<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreventionRule extends Model
{
    protected $fillable = [
        'name',
        'level',
        'category',
        'target_criteria',
        'action_type',
        'action_resource_id',
        'questionnaire_template_id',
        'uspstf_grade',
        'description',
        'evidence_url',
        'frequency_days',
        'is_active',
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'is_active' => 'boolean',
    ];

    public function reminders(): HasMany
    {
        return $this->hasMany(PatientReminder::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(PatientPreventionHistory::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
