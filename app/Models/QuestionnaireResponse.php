<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Orchid\Screen\AsSource;

class QuestionnaireResponse extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'questionnaire_template_id',
        'answers',
        'completed_at',
        'time_spent_seconds',
        'meta',
    ];

    protected $casts = [
        'answers' => 'array',
        'meta' => 'array',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function scheduledQuestionnaire(): HasOne
    {
        return $this->hasOne(PatientScheduledQuestionnaire::class, 'response_id');
    }
}
