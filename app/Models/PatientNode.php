<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientNode extends Model
{
    use HasFactory;

    protected $table = 'patient_nodes';

    protected $fillable = [
        'patient_id',
        'pathway_id',
        'condition_id',
        'access_code',
        'start_date',
        'status',
        'first_completed_at',
        'next_questionnaire_at',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'first_completed_at' => 'datetime',
        'next_questionnaire_at' => 'datetime',
        'start_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function pathway()
    {
        return $this->belongsTo(Pathway::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function scheduledQuestionnaires()
    {
        return $this->hasMany(PatientScheduledQuestionnaire::class);
    }
}
