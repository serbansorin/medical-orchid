<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientReminder extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'prevention_rule_id',
        'due_date',
        'status',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function preventionRule(): BelongsTo
    {
        return $this->belongsTo(PreventionRule::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<=', now());
    }
}
