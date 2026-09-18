<?php

namespace App\Enums;

enum DrugInteractionSeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case Contraindicated = 'contraindicated';

    public function label(): string
    {
        return match ($this) {
            self::Mild => __('Ușoară'),
            self::Moderate => __('Moderată'),
            self::Severe => __('Severă'),
            self::Contraindicated => __('Contraindicată'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Mild => 'info',
            self::Moderate => 'warning',
            self::Severe => 'danger',
            self::Contraindicated => 'danger',
        };
    }

    public function shouldAlert(): bool
    {
        return match ($this) {
            self::Mild => false,
            self::Moderate => true,
            self::Severe => true,
            self::Contraindicated => true,
        };
    }
}
