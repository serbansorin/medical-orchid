<?php

namespace App\Enums;

enum LoyaltyTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => __('Bronz'),
            self::Silver => __('Argint'),
            self::Gold => __('Aur'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bronze => '#CD7F32',
            self::Silver => '#C0C0C0',
            self::Gold => '#FFD700',
        };
    }

    public function multiplier(): float
    {
        return match ($this) {
            self::Bronze => 1.0,
            self::Silver => 1.5,
            self::Gold => 2.0,
        };
    }
}
