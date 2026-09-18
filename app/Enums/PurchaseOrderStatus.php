<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Ciornă'),
            self::Submitted => __('Trimis'),
            self::Confirmed => __('Confirmat'),
            self::Shipped => __('Expediat'),
            self::Received => __('Recepționat'),
            self::Cancelled => __('Anulat'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Confirmed => 'primary',
            self::Shipped => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }
}
