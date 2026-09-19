<?php

namespace App\Models;

use App\Enums\ClinicalRecordStatus;
use App\Enums\ClinicalRecordType;
use App\Enums\PatientReportType;
use Carbon\Carbon;
use Database\Factories\PatientReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Orchid\Screen\AsSource;

class PatientReport extends Model
{
    use AsSource;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'patient_diagnostic_id',
        'parent_id',
        'type',
        'icd_code',
        'title',
        'status',
        'severity',
        'details',
        'notes',
        'occurred_at',
        'reported_by',
        'performed_by',
        'created_by',
        'updated_by',
        'legacy_exam_result_id',
    ];

    protected $casts = [
        'type' => ClinicalRecordType::class,
        'details' => 'array',
        'occurred_at' => 'datetime',
        'is_abnormal' => 'boolean',
    ];

    protected static function newFactory(): PatientReportFactory
    {
        return PatientReportFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $report): void {
            if (array_key_exists('data', $report->attributes)) {
                throw new RuntimeException('PatientReport.data is not a column; write details instead.');
            }

            $type = $report->attributes['type'] ?? null;
            if (is_string($type)) {
                $report->attributes['type'] = ClinicalRecordType::normalize($type);
            }

            $raw = $report->attributes['details'] ?? null;
            if ($raw === null) {
                $report->attributes['details'] = '{}';

                return;
            }

            $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('PatientReport details must be a JSON object.');
            }

            $decoded = array_filter($decoded, static fn (mixed $value): bool => $value !== null);

            try {
                $report->attributes['details'] = $decoded === []
                    ? '{}'
                    : json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('PatientReport details could not be encoded as JSON.', 0, $exception);
            }
        });
    }

    public function setTypeAttribute(mixed $value): void
    {
        $raw = $value instanceof \BackedEnum ? $value->value : (string) $value;

        $this->attributes['type'] = ClinicalRecordType::normalize($raw);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDataAttribute(): array
    {
        return $this->details ?? [];
    }

    public function setDataAttribute(mixed $value): void
    {
        $this->setAttribute('details', is_array($value) ? $value : []);
    }

    public function getExamTypeAttribute(): ?ClinicalRecordType
    {
        return $this->typeEnum();
    }

    public function getExamDateAttribute(): mixed
    {
        return $this->occurred_at ?? $this->dateFromDetails(['exam_date', 'measurement_date']);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->notes;
    }

    public function getFindingsAttribute(): mixed
    {
        return data_get($this->details, 'findings');
    }

    public function getConclusionAttribute(): mixed
    {
        return data_get($this->details, 'conclusion');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnostic::class, 'patient_diagnostic_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(PatientExamResult::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClinicalRecordFile::class, 'clinical_record_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->reportedBy();
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'reported_by', 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->createdBy();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->updatedBy();
    }

    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeForDiagnostic(Builder $query, int $diagnosticId): Builder
    {
        return $query->where('patient_diagnostic_id', $diagnosticId);
    }

    public function scopeOfType(Builder $query, ClinicalRecordType|PatientReportType|string $type): Builder
    {
        $value = $type instanceof \BackedEnum ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function scopeOfTypes(Builder $query, array $types): Builder
    {
        $values = array_map(
            static fn (ClinicalRecordType|PatientReportType|string $type): string => $type instanceof \BackedEnum ? $type->value : $type,
            $types,
        );

        return $query->whereIn('type', $values);
    }

    public function scopeSmallClinical(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::smallClinicalTypeValues());
    }

    public function scopeNotes(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::noteTypeValues());
    }

    public function scopeForWorkspace(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::workspaceTypeValues());
    }

    public function scopeInvestigations(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::investigationTypeValues());
    }

    public function scopeLabs(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::labTypeValues());
    }

    public function scopeImaging(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::imagingTypeValues());
    }

    public function scopeClinicalFacts(Builder $query): Builder
    {
        return $query->ofTypes(ClinicalRecordType::clinicalFactTypeValues());
    }

    public function scopeAbnormal(Builder $query): Builder
    {
        return $query->where('is_abnormal', true);
    }

    public function scopeOccurredBetween(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function typeEnum(): ?ClinicalRecordType
    {
        if ($this->type instanceof ClinicalRecordType) {
            return $this->type;
        }

        $raw = ClinicalRecordType::normalize((string) ($this->getAttributes()['type'] ?? ''));

        return ClinicalRecordType::tryFrom($raw);
    }

    public function typeValue(): string
    {
        return $this->typeEnum()?->value ?? (string) ($this->getAttributes()['type'] ?? '');
    }

    public function isSmallClinicalFact(): bool
    {
        return $this->typeEnum()?->isSmallClinicalType() ?? false;
    }

    public function isNote(): bool
    {
        return $this->typeEnum()?->isNoteType() ?? false;
    }

    public function isWorkspaceCompatible(): bool
    {
        return in_array($this->typeValue(), ClinicalRecordType::workspaceTypeValues(), true);
    }

    public function getConditionNameAttribute(): ?string
    {
        return $this->detailsValue(['condition_name', 'name', 'title']) ?? $this->title;
    }

    public function getAllergenNameAttribute(): ?string
    {
        return $this->detailsValue(['allergen_name', 'allergen', 'substance', 'title']) ?? $this->title;
    }

    public function getAllergenAttribute(): ?string
    {
        return $this->allergen_name;
    }

    public function getReactionDescriptionAttribute(): ?string
    {
        return $this->detailsValue(['reaction_description', 'reaction']) ?? $this->notes;
    }

    public function getReactionAttribute(): ?string
    {
        return $this->reaction_description;
    }

    public function getProcedureNameAttribute(): ?string
    {
        return $this->detailsValue(['procedure_name', 'title']) ?? $this->title;
    }

    public function getVaccineNameAttribute(): ?string
    {
        return $this->detailsValue(['vaccine_name', 'name', 'title']) ?? $this->title;
    }

    public function getDiagnosedDateAttribute(): ?Carbon
    {
        return $this->occurred_at ?? $this->dateFromDetails(['diagnosed_date', 'diagnosed_at', 'onset_date']);
    }

    public function getDiagnosedAtAttribute(): ?Carbon
    {
        return $this->occurred_at ?? $this->dateFromDetails(['diagnosed_at', 'diagnosed_date', 'onset_date']);
    }

    public function getOnsetDateAttribute(): ?Carbon
    {
        return $this->occurred_at ?? $this->dateFromDetails(['onset_date', 'diagnosed_date']);
    }

    public function getAdministrationDateAttribute(): ?Carbon
    {
        return $this->occurred_at ?? $this->dateFromDetails(['administration_date', 'administered_at']);
    }

    public function getNextDoseDueAttribute(): ?Carbon
    {
        return $this->dateFromDetails(['next_dose_due', 'next_dose_date']);
    }

    public function getMeasurementDateAttribute(): ?Carbon
    {
        return $this->occurred_at ?? $this->dateFromDetails(['measurement_date', 'recorded_at']) ?? $this->created_at;
    }

    public function getIsActiveAttribute(): bool
    {
        $status = $this->status instanceof ClinicalRecordStatus
            ? $this->status->value
            : (string) $this->status;

        return ! in_array($status, ['resolved', 'cancelled', 'refused', 'not_administered'], true);
    }

    public function getSummaryAttribute(): string
    {
        $details = $this->details ?? [];

        return match ($this->typeValue()) {
            ClinicalRecordType::Condition->value => $this->condition_name ?? $this->title ?? 'Afecțiune',
            ClinicalRecordType::Allergy->value => $this->allergen_name ?? $this->title ?? 'Alergie',
            ClinicalRecordType::VitalSign->value => collect([
                isset($details['blood_pressure_systolic'], $details['blood_pressure_diastolic'])
                    ? 'TA '.$details['blood_pressure_systolic'].'/'.$details['blood_pressure_diastolic']
                    : null,
                isset($details['heart_rate']) ? 'Puls '.$details['heart_rate'] : null,
                isset($details['temperature']) ? 'Temp '.$details['temperature'].'°C' : null,
                isset($details['weight']) ? 'Greutate '.$details['weight'].' kg' : null,
            ])->filter()->implode(' • ') ?: ($this->title ?? 'Semne vitale'),
            ClinicalRecordType::Vaccination->value => $this->vaccine_name ?? $this->title ?? 'Vaccinare',
            ClinicalRecordType::FamilyHistory->value => $this->detailsValue(['condition_name', 'condition', 'title']) ?? $this->title ?? 'Istoric familial',
            ClinicalRecordType::SurgicalHistory->value => $this->procedure_name ?? $this->title ?? 'Istoric chirurgical',
            ClinicalRecordType::Procedure->value => $this->procedure_name ?? $this->title ?? 'Procedură',
            ClinicalRecordType::LabPanel->value,
            ClinicalRecordType::BloodTest->value,
            ClinicalRecordType::UrineTest->value => $this->title ?? data_get($details, 'lab_name') ?? 'Analize',
            ClinicalRecordType::Ecg->value,
            ClinicalRecordType::Ekg->value => $this->title ?? data_get($details, 'interpretation') ?? 'ECG',
            ClinicalRecordType::Mri->value,
            ClinicalRecordType::Ct->value,
            ClinicalRecordType::Xray->value,
            ClinicalRecordType::Ultrasound->value => $this->title ?? data_get($details, 'body_region') ?? 'Imagistică',
            ClinicalRecordType::Biopsy->value => $this->title ?? data_get($details, 'specimen_site') ?? 'Biopsie',
            default => $this->title ?? $this->notes ?? 'Raport clinic',
        };
    }

    private function detailsValue(array $keys): mixed
    {
        $details = $this->details ?? [];

        foreach ($keys as $key) {
            $value = data_get($details, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function dateFromDetails(array $keys): ?Carbon
    {
        $value = $this->detailsValue($keys);

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
