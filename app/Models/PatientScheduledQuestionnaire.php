<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientScheduledQuestionnaire extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_node_id',
        'pathway_scheduler_id',
        'questionnaire_template_id',
        'scheduled_for',
        'status',
        'completed_at',
        'response_id',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patientNode(): BelongsTo
    {
        return $this->belongsTo(PatientNode::class);
    }

    public function pathwayScheduler(): BelongsTo
    {
        return $this->belongsTo(PathwayScheduler::class);
    }

    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireResponse::class, 'response_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('scheduled_for', '<=', now())->where('status', 'pending');
    }
}
