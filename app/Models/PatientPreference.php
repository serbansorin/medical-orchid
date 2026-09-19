<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientPreference extends Model
{
    use AsSource;

    protected $fillable = [
        'patient_id',
        'preferred_days',
        'preferred_time_ranges',
        'notification_channels',
    ];

    protected $casts = [
        'preferred_days' => 'array',
        'preferred_time_ranges' => 'array',
        'notification_channels' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
