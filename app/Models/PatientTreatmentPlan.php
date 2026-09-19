<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Screen\AsSource;

class PatientTreatmentPlan extends Model
{
    use AsSource;

    use HasFactory;

    protected $fillable = [
        'patient_id',
        'treatment_id',
        'condition_id',
        'created_by',
        'name',
        'notes',
        'medications',
        'schedule',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'medications' => 'array',
        'schedule' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(Condition::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
