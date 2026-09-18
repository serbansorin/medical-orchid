<?php

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case FixedAmount = 'fixed_amount';
    case BuyXGetY = 'buy_x_get_y';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => __('Procentaj'),
            self::FixedAmount => __('Sumă fixă'),
            self::BuyXGetY => __('Cumperi X primești Y'),
        };
    }
}
