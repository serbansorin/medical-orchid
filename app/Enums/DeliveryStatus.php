<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('În așteptare'),
            self::Assigned => __('Atribuit'),
            self::PickedUp => __('Preluat'),
            self::InTransit => __('În tranzit'),
            self::Delivered => __('Livrat'),
            self::Failed => __('Eșuat'),
            self::Cancelled => __('Anulat'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Assigned => 'info',
            self::PickedUp => 'primary',
            self::InTransit => 'warning',
            self::Delivered => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'danger',
        };
    }
}
