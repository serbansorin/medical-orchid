<?php

namespace App\Enums;

enum AllergyType: string
{
    case FOOD = 'food';
    case DRUG = 'drug';
    case ENVIRONMENTAL = 'environmental';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FOOD => 'Alimentară',
            self::DRUG => 'Medicament',
            self::ENVIRONMENTAL => 'Mediu înconjurător',
            self::OTHER => 'Altă',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FOOD => 'warning',
            self::DRUG => 'danger',
            self::ENVIRONMENTAL => 'info',
            self::OTHER => 'gray',
        };
    }

    public static function options(): array
    {
        return [
            self::FOOD->value => self::FOOD->label(),
            self::DRUG->value => self::DRUG->label(),
            self::ENVIRONMENTAL->value => self::ENVIRONMENTAL->label(),
            self::OTHER->value => self::OTHER->label(),
        ];
    }
}
