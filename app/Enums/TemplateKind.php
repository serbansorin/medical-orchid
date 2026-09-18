<?php

namespace App\Enums;

enum TemplateKind: string
{
    case ClinicalNote = 'clinical_note';
    case Letter = 'letter';
    case Questionnaire = 'questionnaire';
    case Consent = 'consent';

    public function label(): string
    {
        return match ($this) {
            self::ClinicalNote => 'Notă clinică',
            self::Letter => 'Scrisoare',
            self::Questionnaire => 'Chestionar',
            self::Consent => 'Consimțământ',
        };
    }
}
