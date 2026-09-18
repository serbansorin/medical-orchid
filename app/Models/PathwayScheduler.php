<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PathwayScheduler extends Model
{
    protected $fillable = [
        'pathway_id',
        'questionnaire_template_id',
        'trigger_type',
        'day_offset',
        'repeat_interval',
        'delivery_type',
        'target_pages',
        'is_required',
        'order',
        'meta',
    ];

    protected $casts = [
        'target_pages' => 'array',
        'meta' => 'array',
        'is_required' => 'boolean',
    ];

    public function pathway(): BelongsTo
    {
        return $this->belongsTo(Pathway::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function scheduledQuestionnaires(): HasMany
    {
        return $this->hasMany(PatientScheduledQuestionnaire::class);
    }
}
