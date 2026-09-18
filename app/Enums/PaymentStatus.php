<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Partial = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('În așteptare'),
            self::Paid => __('Plătit'),
            self::Refunded => __('Rambursat'),
            self::Partial => __('Parțial'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Refunded => 'danger',
            self::Partial => 'info',
        };
    }
}
