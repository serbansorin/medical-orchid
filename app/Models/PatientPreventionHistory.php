<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientPreventionHistory extends Model
{
    use AsSource;

    protected $table = 'patient_prevention_history';

    protected $fillable = [
        'patient_id',
        'prevention_rule_id',
        'completed_at',
        'result',
        'next_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'next_due' => 'date',
        'result' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function preventionRule(): BelongsTo
    {
        return $this->belongsTo(PreventionRule::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }
}
