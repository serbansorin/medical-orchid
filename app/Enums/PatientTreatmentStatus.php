<?php

namespace App\Enums;

enum PatientTreatmentStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activ',
            self::COMPLETED => 'Completat',
            self::CANCELLED => 'Anulat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-play',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }
}
