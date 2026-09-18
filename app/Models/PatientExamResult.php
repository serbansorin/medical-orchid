<?php

namespace App\Models;

use App\Enums\PatientExamType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PatientExamResult extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'patient_report_id',
        'patient_diagnostic_id',
        'exam_type',
        'title',
        'description',
        'exam_date',
        'performed_by',
        'findings',
        'conclusion',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'exam_type' => PatientExamType::class,
        'exam_date' => 'date',
        'status' => 'string',
    ];

    /**
     * Get the patient that owns the exam result.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientReport(): BelongsTo
    {
        return $this->belongsTo(PatientReport::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(PatientExamResultFile::class);
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnostic::class, 'patient_diagnostic_id');
    }

    public function scopeForDiagnostic($query, int $diagnosticId)
    {
        return $query->where('patient_diagnostic_id', $diagnosticId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('exam_date', '>=', now()->subDays($days));
    }

    public function scopeByExamType($query, PatientExamType $examType)
    {
        return $query->where('exam_type', $examType);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('exam_files')
            ->useDisk('public')
            ->acceptsFile(function ($file) {
                return in_array($file->mimeType, [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/dicom',
                ]);
            });
    }
}
