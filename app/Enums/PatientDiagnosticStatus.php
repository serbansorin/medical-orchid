<?php

namespace App\Enums;

enum PatientDiagnosticStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case REFERRED = 'referred';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activ',
            self::COMPLETED => 'Completat',
            self::REFERRED => 'Referit',
            self::CANCELLED => 'Anulat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'primary',
            self::COMPLETED => 'success',
            self::REFERRED => 'warning',
            self::CANCELLED => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-play',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::REFERRED => 'heroicon-o-arrow-right-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }
}
