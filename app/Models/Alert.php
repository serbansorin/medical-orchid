<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'alert_rule_id',
        'patient_id',
        'patient_scheduled_questionnaire_id',
        'severity',
        'status',
        'resolved_by',
        'resolved_at',
        'context',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'context' => 'array',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AlertRule::class, 'alert_rule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function scheduledQuestionnaire(): BelongsTo
    {
        return $this->belongsTo(PatientScheduledQuestionnaire::class, 'patient_scheduled_questionnaire_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
