<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientDocument extends Model
{
    protected $fillable = [
        'patient_id',
        'uploaded_by',
        'title',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'notes',
        // morphTo relation fields
        'documentable_id',
        'documentable_type',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper to get file URL
    public function getUrlAttribute()
    {
        return asset('storage/'.$this->file_path);
    }
}
