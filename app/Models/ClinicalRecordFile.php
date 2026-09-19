<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Orchid\Screen\AsSource;

class ClinicalRecordFile extends Model implements HasMedia
{
    use AsSource;

    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'clinical_record_files';

    protected $fillable = [
        'clinical_record_id',
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

    public function clinicalRecord(): BelongsTo
    {
        return $this->belongsTo(PatientReport::class, 'clinical_record_id');
    }

    /** @deprecated Use clinicalRecord() */
    public function patientExamResult(): BelongsTo
    {
        return $this->clinicalRecord();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('exam_file')
            ->useDisk('public')
            ->singleFile();
    }
}
