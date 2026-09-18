<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Unified clinical record type — union of PatientExamType + PatientReportType.
 * `ekg` is a deprecated read-only alias that normalises to `ecg` on write.
 */
enum ClinicalRecordType: string implements HasColor, HasIcon, HasLabel
{
    // Investigation (from PatientExamType)
    case LabPanel = 'lab_panel';
    case BloodTest = 'blood_test';
    case UrineTest = 'urine_test';
    case Ecg = 'ecg';
    /** @deprecated Use Ecg */
    case Ekg = 'ekg';
    case Mri = 'mri';
    case Ct = 'ct';
    case Xray = 'xray';
    case Ultrasound = 'ultrasound';
    case Biopsy = 'biopsy';
    case Other = 'other';

    // Clinical facts / events / narrative (from PatientReportType)
    case VitalSign = 'vital_sign';
    case Condition = 'condition';
    case Allergy = 'allergy';
    case FamilyHistory = 'family_history';
    case SurgicalHistory = 'surgical_history';
    case MedicalNote = 'medical_note';
    case Procedure = 'procedure';
    case Vaccination = 'vaccination';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function getIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return $this->icon();
    }

    public function label(): string
    {
        return match ($this) {
            self::LabPanel => 'Panel de Laborator',
            self::BloodTest => 'Analize de Sânge',
            self::UrineTest => 'Analize de Urină',
            self::Ecg, self::Ekg => 'ECG (Electrocardiogramă)',
            self::Mri => 'RMN (Rezonanță Magnetică)',
            self::Ct => 'CT (Tomografie Computerizată)',
            self::Xray => 'Radiografie',
            self::Ultrasound => 'Ecografie',
            self::Biopsy => 'Biopsie',
            self::Other => 'Altceva',
            self::VitalSign => 'Semne Vitale',
            self::Condition => 'Afecțiune / Diagnostic',
            self::Allergy => 'Alergie',
            self::FamilyHistory => 'Istoric familial',
            self::SurgicalHistory => 'Istoric chirurgical',
            self::MedicalNote => 'Notă Medicală',
            self::Procedure => 'Procedură',
            self::Vaccination => 'Vaccinare',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::LabPanel => 'Lab Panel',
            self::BloodTest => 'Blood Test',
            self::UrineTest => 'Urine Test',
            self::Ecg, self::Ekg => 'ECG (Electrocardiogram)',
            self::Mri => 'MRI (Magnetic Resonance Imaging)',
            self::Ct => 'CT (Computed Tomography)',
            self::Xray => 'X-Ray',
            self::Ultrasound => 'Ultrasound',
            self::Biopsy => 'Biopsy',
            self::Other => 'Other',
            self::VitalSign => 'Vital Signs',
            self::Condition => 'Condition / Diagnosis',
            self::Allergy => 'Allergy',
            self::FamilyHistory => 'Family History',
            self::SurgicalHistory => 'Surgical History',
            self::MedicalNote => 'Medical Note',
            self::Procedure => 'Procedure',
            self::Vaccination => 'Vaccination',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LabPanel, self::BloodTest, self::UrineTest => 'danger',
            self::Ecg, self::Ekg, self::Biopsy => 'warning',
            self::Mri, self::Ct, self::Xray, self::Ultrasound => 'info',
            self::Condition => 'danger',
            self::Allergy => 'warning',
            self::VitalSign => 'info',
            self::FamilyHistory => 'info',
            self::SurgicalHistory, self::Procedure => 'warning',
            self::MedicalNote, self::Other => 'gray',
            self::Vaccination => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LabPanel, self::BloodTest, self::UrineTest => 'heroicon-o-beaker',
            self::Ecg, self::Ekg => 'heroicon-o-heart',
            self::Mri, self::Ct, self::Xray, self::Ultrasound => 'heroicon-o-photo',
            self::Biopsy => 'heroicon-o-scissors',
            self::Condition => 'heroicon-o-exclamation-triangle',
            self::Allergy => 'heroicon-o-beaker',
            self::VitalSign => 'heroicon-o-heart',
            self::FamilyHistory => 'heroicon-o-users',
            self::SurgicalHistory, self::Procedure => 'heroicon-o-wrench-screwdriver',
            self::MedicalNote => 'heroicon-o-document-text',
            self::Vaccination => 'heroicon-o-shield-check',
            self::Other => 'heroicon-o-ellipsis-horizontal',
        };
    }

    public function fhirResource(): string
    {
        return match ($this) {
            self::LabPanel, self::BloodTest, self::UrineTest => 'Observation',
            self::Ecg, self::Ekg, self::Mri, self::Ct, self::Xray, self::Ultrasound, self::Biopsy => 'DiagnosticReport',
            self::Condition => 'Condition',
            self::Allergy => 'AllergyIntolerance',
            self::VitalSign => 'Observation',
            self::FamilyHistory => 'FamilyMemberHistory',
            self::SurgicalHistory, self::Procedure => 'Procedure',
            self::MedicalNote => 'ClinicalImpression',
            self::Vaccination => 'Immunization',
            self::Other => 'Observation',
        };
    }

    /** @return list<self> */
    public static function investigationTypes(): array
    {
        return [
            self::LabPanel,
            self::BloodTest,
            self::UrineTest,
            self::Ecg,
            self::Ekg,
            self::Mri,
            self::Ct,
            self::Xray,
            self::Ultrasound,
            self::Biopsy,
            self::Other,
        ];
    }

    /** @return list<self> */
    public static function clinicalFactTypes(): array
    {
        return [
            self::Condition,
            self::Allergy,
            self::FamilyHistory,
            self::SurgicalHistory,
        ];
    }

    /** @return list<self> */
    public static function eventTypes(): array
    {
        return [
            self::Procedure,
            self::Vaccination,
            self::VitalSign,
        ];
    }

    /** @return list<self> */
    public static function narrativeTypes(): array
    {
        return [self::MedicalNote];
    }

    /** @return list<self> */
    public static function labTypes(): array
    {
        return [self::LabPanel, self::BloodTest, self::UrineTest];
    }

    /** @return list<self> */
    public static function imagingTypes(): array
    {
        return [self::Mri, self::Ct, self::Xray, self::Ultrasound];
    }

    /** @return list<string> */
    public static function valuesOf(array $cases): array
    {
        return array_map(static fn (self $t): string => $t->value, $cases);
    }

    /** @return list<string> */
    public static function investigationTypeValues(): array
    {
        return self::valuesOf(self::investigationTypes());
    }

    /** @return list<string> */
    public static function clinicalFactTypeValues(): array
    {
        return self::valuesOf(self::clinicalFactTypes());
    }

    /** @return list<string> */
    public static function eventTypeValues(): array
    {
        return self::valuesOf(self::eventTypes());
    }

    /** @return list<string> */
    public static function narrativeTypeValues(): array
    {
        return self::valuesOf(self::narrativeTypes());
    }

    /** @return list<string> */
    public static function labTypeValues(): array
    {
        return self::valuesOf(self::labTypes());
    }

    /** @return list<string> */
    public static function imagingTypeValues(): array
    {
        return self::valuesOf(self::imagingTypes());
    }

    // --- Compatibility helpers (preserve PatientReportType contracts) ---

    public function isDiagnosticType(): bool
    {
        return in_array($this, [self::Condition, self::VitalSign], true);
    }

    public function isMedicalHistoryType(): bool
    {
        return in_array($this, [
            self::Allergy,
            self::Vaccination,
            self::FamilyHistory,
            self::SurgicalHistory,
        ], true);
    }

    public function isSmallClinicalType(): bool
    {
        return in_array($this, self::smallClinicalTypes(), true);
    }

    public function isNoteType(): bool
    {
        return in_array($this, self::noteTypes(), true);
    }

    public function isInvestigationType(): bool
    {
        return in_array($this, self::investigationTypes(), true);
    }

    /** @return list<self> */
    public static function smallClinicalTypes(): array
    {
        return [
            self::Condition,
            self::Allergy,
            self::VitalSign,
            self::FamilyHistory,
            self::SurgicalHistory,
            self::Procedure,
            self::Vaccination,
        ];
    }

    /** @return list<string> */
    public static function smallClinicalTypeValues(): array
    {
        return self::valuesOf(self::smallClinicalTypes());
    }

    /** @return list<self> */
    public static function noteTypes(): array
    {
        return [self::MedicalNote];
    }

    /** @return list<string> */
    public static function noteTypeValues(): array
    {
        return self::valuesOf(self::noteTypes());
    }

    /** @return list<string> */
    public static function historyTypeValues(): array
    {
        return array_values(array_unique([
            ...self::smallClinicalTypeValues(),
            ...self::noteTypeValues(),
        ]));
    }

    /** @return list<string> */
    public static function workspaceTypeValues(): array
    {
        return self::historyTypeValues();
    }

    /** @return list<string> */
    public static function medicalHistoryTypeValues(): array
    {
        return self::valuesOf([
            self::Allergy,
            self::Vaccination,
            self::FamilyHistory,
            self::SurgicalHistory,
        ]);
    }

    /** @return list<self> */
    public static function diagnosticTypes(): array
    {
        return [self::Condition, self::VitalSign];
    }

    /** @return array<string, string> */
    public static function labCategories(): array
    {
        return [
            'hematologie' => 'Hematologie',
            'biochimie' => 'Biochimie',
            'endocrinologie' => 'Endocrinologie',
            'inflamatie' => 'Inflamație / infecție',
            'urina' => 'Urina',
            'coagulare' => 'Coagulare',
            'vitamine' => 'Vitamine și carențe',
            'altele' => 'Altele',
        ];
    }

    public static function normalize(string $value): string
    {
        return $value === self::Ekg->value ? self::Ecg->value : $value;
    }
}
