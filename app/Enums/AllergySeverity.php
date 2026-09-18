<?php

namespace App\Enums;

enum AllergySeverity: string
{
    case MILD = 'mild';
    case MODERATE = 'moderate';
    case SEVERE = 'severe';
    case LIFE_THREATENING = 'life_threatening';

    public function label(): string
    {
        return match ($this) {
            self::MILD => 'Ușoară',
            self::MODERATE => 'Moderată',
            self::SEVERE => 'Severă',
            self::LIFE_THREATENING => 'Pericol de viață',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MILD => 'success',
            self::MODERATE => 'warning',
            self::SEVERE => 'danger',
            self::LIFE_THREATENING => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::MILD->value => self::MILD->label(),
            self::MODERATE->value => self::MODERATE->label(),
            self::SEVERE->value => self::SEVERE->label(),
            self::LIFE_THREATENING->value => self::LIFE_THREATENING->label(),
        ];
    }
}
