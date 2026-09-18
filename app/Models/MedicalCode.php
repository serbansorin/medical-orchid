<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalCode extends \Illuminate\Database\Eloquent\Model
{
    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Vaccinările care folosesc acest cod CVX.
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class, 'cvx_code_id');
    }

    /**
     * Condițiile pacienților care folosesc acest cod ICD.
     */
    public function patientConditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class, 'icd_code_id');
    }

    /**
     * Istoricul familial care folosește acest cod ICD.
     */
    public function familyHistories(): HasMany
    {
        return $this->hasMany(FamilyHistory::class, 'icd_code_id');
    }

    /**
     * Istoricul chirurgical care folosește acest cod de procedură.
     */
    public function surgicalHistories(): HasMany
    {
        return $this->hasMany(SurgicalHistory::class, 'procedure_code_id');
    }

    /**
     * Procedurile care folosesc acest cod CPT.
     */
    public function procedures(): HasMany
    {
        return $this->hasMany(Procedure::class, 'procedure_code_id');
    }

    protected $table = 'medical_codes';

    protected $casts = [
        'code_type' => 'int',
        'units' => 'int',
        'fee' => 'float',
        'active' => 'bool',
        'reportable' => 'bool',
        'effective_date' => 'date',
        'expiration_date' => 'date',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'code',
        'code_system',
        'code_text',
        'code_text_short',
        'code_type',
        'modifier',
        'units',
        'fee',
        'superbill',
        'taxrates',
        'related_code',
        'category',
        'subcategory',
        'unit',
        'reference_range',
        'metadata',
        'active',
        'reportable',
        'external_id',
        'external_source',
        'effective_date',
        'expiration_date',
        'notes',
    ];

    // =========================================================================
    // SCOPES - Query by code system
    // =========================================================================

    /**
     * Scope pentru coduri ICD-10 (diagnostice moderne).
     */
    public function scopeIcd10(Builder $query): Builder
    {
        return $query->where('code_system', 'ICD10');
    }

    /**
     * Scope pentru coduri ICD-9 (diagnostice legacy).
     */
    public function scopeIcd9(Builder $query): Builder
    {
        return $query->where('code_system', 'ICD9');
    }

    /**
     * Scope pentru coduri ICD (ambele versiuni).
     */
    public function scopeIcd(Builder $query): Builder
    {
        return $query->whereIn('code_system', ['ICD9', 'ICD10']);
    }

    /**
     * Scope pentru coduri CVX (vaccinuri).
     */
    public function scopeCvx(Builder $query): Builder
    {
        return $query->where('code_system', 'CVX');
    }

    /**
     * Scope pentru coduri CPT (proceduri).
     */
    public function scopeCpt(Builder $query): Builder
    {
        return $query->where('code_system', 'CPT');
    }

    /**
     * Scope pentru coduri SNOMED CT.
     */
    public function scopeSnomed(Builder $query): Builder
    {
        return $query->where('code_system', 'SNOMED');
    }

    /**
     * Scope pentru coduri LOINC (laborator).
     */
    public function scopeLoinc(Builder $query): Builder
    {
        return $query->where('code_system', 'LOINC');
    }

    /**
     * Scope pentru coduri RXNORM (medicamente).
     */
    public function scopeRxnorm(Builder $query): Builder
    {
        return $query->where('code_system', 'RXNORM');
    }

    /**
     * Scope pentru coduri active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope pentru coduri care necesită raportare.
     */
    public function scopeReportable(Builder $query): Builder
    {
        return $query->where('reportable', true);
    }

    /**
     * Scope pentru căutare după cod sau text.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "{$search}%")
                ->orWhere('code_text', 'like', "%{$search}%")
                ->orWhere('code_text_short', 'like', "%{$search}%");
        });
    }

    /**
     * Scope pentru coduri dintr-o anumită categorie.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get display name (short name if available, otherwise full name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->code_text_short ?? $this->code_text;
    }

    /**
     * Get formatted code with system prefix.
     * Example: "ICD10:J10.1" or "CVX:3"
     */
    public function getFormattedCodeAttribute(): string
    {
        return "{$this->code_system}:{$this->code}";
    }

    /**
     * Get label for display in selects (code + short name).
     */
    public function getLabelAttribute(): string
    {
        $shortName = $this->code_text_short ?? mb_substr($this->code_text, 0, 50);

        return "{$this->code} - {$shortName}";
    }

    // =========================================================================
    // STATIC METHODS
    // =========================================================================

    /**
     * Find a code by its code and system.
     */
    public static function findByCode(string $code, string $system): ?self
    {
        return static::where('code', $code)
            ->where('code_system', strtoupper($system))
            ->first();
    }

    /**
     * Get all available code systems.
     */
    public static function getCodeSystems(): array
    {
        return [
            'ICD10' => 'ICD-10 (Diagnostice)',
            'ICD9' => 'ICD-9 (Diagnostice Legacy)',
            'CPT' => 'CPT (Proceduri)',
            'CVX' => 'CVX (Vaccinuri)',
            'SNOMED' => 'SNOMED CT',
            'LOINC' => 'LOINC (Laborator)',
            'RXNORM' => 'RxNorm (Medicamente)',
            'HCPCS' => 'HCPCS',
            'NDC' => 'NDC (Medicamente)',
            'ATC' => 'ATC (Medicamente)',
        ];
    }
}
