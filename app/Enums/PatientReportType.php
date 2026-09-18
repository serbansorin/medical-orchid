<?php

namespace App\Enums;

enum PatientReportType: string
{
    case Condition = 'condition';
    case Allergy = 'allergy';
    case VitalSign = 'vital_sign';
    case FamilyHistory = 'family_history';
    case SurgicalHistory = 'surgical_history';
    case MedicalNote = 'medical_note';
    case Procedure = 'procedure';
    case Vaccination = 'vaccination';

    public function label(): string
    {
        return match ($this) {
            self::Condition => 'Afecțiune / Diagnostic',
            self::Allergy => 'Alergie',
            self::VitalSign => 'Semne Vitale',
            self::FamilyHistory => 'Istoric familial',
            self::SurgicalHistory => 'Istoric chirurgical',
            self::MedicalNote => 'Notă Medicală',
            self::Procedure => 'Procedură',
            self::Vaccination => 'Vaccinare',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Condition => 'danger',
            self::Allergy => 'warning',
            self::VitalSign => 'info',
            self::FamilyHistory => 'info',
            self::SurgicalHistory => 'warning',
            self::MedicalNote => 'gray',
            self::Procedure => 'warning',
            self::Vaccination => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Condition => 'heroicon-o-exclamation-triangle',
            self::Allergy => 'heroicon-o-beaker',
            self::VitalSign => 'heroicon-o-heart',
            self::FamilyHistory => 'heroicon-o-users',
            self::SurgicalHistory => 'heroicon-o-wrench-screwdriver',
            self::MedicalNote => 'heroicon-o-document-text',
            self::Procedure => 'heroicon-o-wrench-screwdriver',
            self::Vaccination => 'heroicon-o-shield-check',
        };
    }

    /**
     * FHIR Resource mapping for future API compatibility.
     */
    public function fhirResource(): string
    {
        return match ($this) {
            self::Condition => 'Condition',
            self::Allergy => 'AllergyIntolerance',
            self::VitalSign => 'Observation',
            self::FamilyHistory => 'FamilyMemberHistory',
            self::SurgicalHistory => 'Procedure',
            self::MedicalNote => 'ClinicalImpression',
            self::Procedure => 'Procedure',
            self::Vaccination => 'Immunization',
        };
    }

    public function isDiagnosticType(): bool
    {
        return in_array($this, [
            self::Condition,
            self::VitalSign,
        ], true);
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

    public static function smallClinicalTypeValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::smallClinicalTypes(),
        );
    }

    public static function noteTypes(): array
    {
        return [
            self::MedicalNote,
        ];
    }

    public static function noteTypeValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::noteTypes(),
        );
    }

    public static function historyTypeValues(): array
    {
        return array_values(array_unique([
            ...self::smallClinicalTypeValues(),
            ...self::noteTypeValues(),
        ]));
    }

    public static function workspaceTypeValues(): array
    {
        return self::historyTypeValues();
    }

    public static function medicalHistoryTypeValues(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            [
                self::Allergy,
                self::Vaccination,
                self::FamilyHistory,
                self::SurgicalHistory,
            ],
        );
    }

    public static function diagnosticTypes(): array
    {
        return [
            self::Condition,
            self::VitalSign,
        ];
    }

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
}
