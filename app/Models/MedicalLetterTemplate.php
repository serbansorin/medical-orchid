<?php

namespace App\Models;

use App\Support\MedicalLetters\MedicalLetterTemplateSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalLetterTemplate extends Model
{
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'name',
        'type',
        'title',
        'schema',
        'availability',
        'availability_id',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'schema' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ──

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    public function letters(): HasMany
    {
        return $this->hasMany(MedicalLetter::class, 'template_id');
    }

    // ── Scopes ──

    /**
     * Filter templates available to the given user context.
     * Shows: global + matching organization + matching clinic + user-owned.
     */
    public function scopeAvailableTo(Builder $query, ?int $userId = null, ?int $clinicId = null, ?int $orgId = null): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($userId, $clinicId, $orgId) {
                // Global templates (available to everyone)
                $q->where('availability', 'global');

                // Organization-level templates
                if ($orgId) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('availability', 'organization')
                        ->where('availability_id', $orgId));
                }

                // Clinic-level templates
                if ($clinicId) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('availability', 'clinic')
                        ->where('availability_id', $clinicId));
                }

                // User-owned templates
                if ($userId) {
                    $q->orWhere(fn (Builder $sub) => $sub
                        ->where('availability', 'user')
                        ->where('availability_id', $userId));
                }
            });
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->where('availability', 'global');
    }

    // ── Helpers ──

    /**
     * Get the schema fields array.
     */
    public function getSchemaFields(): array
    {
        return MedicalLetterTemplateSchema::fields($this->schema);
    }

    public function getSchemaSections(): array
    {
        return MedicalLetterTemplateSchema::sections($this->schema);
    }

    public function getSchemaDefaults(): array
    {
        return MedicalLetterTemplateSchema::defaults($this->schema);
    }

    /**
     * Get a human-readable availability label.
     */
    public function getAvailabilityLabelAttribute(): string
    {
        return match ($this->availability) {
            'global' => 'Global',
            'organization' => 'Organizație',
            'clinic' => 'Clinică',
            'user' => 'Personal',
            default => $this->availability,
        };
    }
}
