<?php

namespace App\Enums;

enum PatientExamType: string
{
    case LAB_PANEL = 'lab_panel';
    case BLOOD_TEST = 'blood_test';
    case URINE_TEST = 'urine_test';
    case ECG = 'ecg';
    case EKG = 'ekg';
    case MRI = 'mri';
    case CT = 'ct';
    case XRAY = 'xray';
    case ULTRASOUND = 'ultrasound';
    case BIOPSY = 'biopsy';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LAB_PANEL => 'Panel de Laborator',
            self::BLOOD_TEST => 'Analize de Sânge',
            self::URINE_TEST => 'Analize de Urină',
            self::ECG => 'ECG (Electrocardiogramă)',
            self::EKG => 'EKG (Electrocardiogramă)',
            self::MRI => 'RMN (Rezonanță Magnetică)',
            self::CT => 'CT (Tomografie Computerizată)',
            self::XRAY => 'Radiografie',
            self::ULTRASOUND => 'Ecografie',
            self::BIOPSY => 'Biopsie',
            self::OTHER => 'Altceva',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::LAB_PANEL => 'Lab Panel',
            self::BLOOD_TEST => 'Blood Test',
            self::URINE_TEST => 'Urine Test',
            self::ECG => 'ECG (Electrocardiogram)',
            self::EKG => 'EKG (Electrocardiogram)',
            self::MRI => 'MRI (Magnetic Resonance Imaging)',
            self::CT => 'CT (Computed Tomography)',
            self::XRAY => 'X-Ray',
            self::ULTRASOUND => 'Ultrasound',
            self::BIOPSY => 'Biopsy',
            self::OTHER => 'Other',
        };
    }
}
