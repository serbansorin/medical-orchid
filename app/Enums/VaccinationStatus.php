<?php

namespace App\Enums;

enum VaccinationStatus: string
{
    case SCHEDULED = 'scheduled';
    case ADMINISTERED = 'administered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Programat',
            self::ADMINISTERED => 'Administrat',
            self::COMPLETED => 'Finalizat',
            self::CANCELLED => 'Anulat',
            self::EXPIRED => 'Expirat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'warning',
            self::ADMINISTERED => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
            self::EXPIRED => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::SCHEDULED->value => self::SCHEDULED->label(),
            self::ADMINISTERED->value => self::ADMINISTERED->label(),
            self::COMPLETED->value => self::COMPLETED->label(),
            self::CANCELLED->value => self::CANCELLED->label(),
            self::EXPIRED->value => self::EXPIRED->label(),
        ];
    }
}
