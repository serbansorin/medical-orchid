<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlertRule extends Model
{
    protected $fillable = [
        'questionnaire_template_id',
        'name',
        'conditions',
        'severity',
        'target_role',
        'message_template',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
