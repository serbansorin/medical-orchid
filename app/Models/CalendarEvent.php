<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'clinic_id',
        'doctor_id',
        'patient_id',
        'appointment_id',
        'type',
        'status',
        'title',
        'description',
        'location',
        'url',
        'start_at',
        'end_at',
        'all_day',
        'metadata',
        'reminders',
        'color',
        'external_provider',
        'external_calendar_id',
        'external_event_id',
        'external_etag',
        'external_synced_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
            'metadata' => 'array',
            'reminders' => 'array',
            'external_synced_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    protected function isExternal(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->external_provider) && filled($this->external_event_id),
        );
    }
}
