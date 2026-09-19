<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Orchid\Screen\AsSource;

class PatientExamResultFile extends Model implements HasMedia
{
    use AsSource;

    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'patient_exam_result_id',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'size',
        'description',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function patientExamResult(): BelongsTo
    {
        return $this->belongsTo(PatientExamResult::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('exam_file')
            ->useDisk('public')
            ->singleFile();
    }
}
