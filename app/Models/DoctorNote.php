<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorNote extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'related_appointment_id',
        'note_type',
        'content',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'visibility',
        'is_transcribed',
        'transcription',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_transcribed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the patient this note belongs to.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor who created this note.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the related appointment (if any).
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'related_appointment_id');
    }

    /**
     * Get file URL if file exists.
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return asset('storage/'.$this->file_path);
        }

        return null;
    }

    /**
     * Scope: Only notes visible to current doctor.
     */
    public function scopeVisibleTo($query, $doctorId)
    {
        return $query->where(function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId) // Own notes
                ->orWhere('visibility', 'doctors_only') // Shared with doctors
                ->orWhere('visibility', 'public'); // Public notes
        });
    }

    /**
     * Scope: Only private notes.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope: Only doctors-only notes.
     */
    public function scopeDoctorsOnly($query)
    {
        return $query->where('visibility', 'doctors_only');
    }

    /**
     * Scope: Only public notes.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope: Filter by note type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('note_type', $type);
    }

    /**
     * Check if note has audio/video/file attachment.
     */
    public function hasFile(): bool
    {
        return ! in_array($this->note_type, ['text']) && ! empty($this->file_path);
    }

    /**
     * Check if note is viewable by given doctor.
     */
    public function isViewableBy(int $doctorId): bool
    {
        return $this->doctor_id === $doctorId
            || $this->visibility === 'doctors_only'
            || $this->visibility === 'public';
    }
}
