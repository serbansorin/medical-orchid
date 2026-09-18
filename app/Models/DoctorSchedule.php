<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends \Illuminate\Database\Eloquent\Model
{
    /** @use HasFactory<\Database\Factories\DoctorScheduleFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'doctor_id' => 'int',
        'clinic_id' => 'int',
        'available_day' => 'int',
        'from' => 'string',
        'to' => 'string',
    ];

    protected $table = 'doctor_schedules';

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Nullable clinic: null means the hours apply to every clinic the doctor works at.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
