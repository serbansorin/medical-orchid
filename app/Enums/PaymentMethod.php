<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Voucher = 'voucher';
    case Insurance = 'insurance';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Numerar'),
            self::Card => __('Card'),
            self::Voucher => __('Voucher'),
            self::Insurance => __('Asigurare'),
            self::BankTransfer => __('Transfer bancar'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'heroicon-o-banknotes',
            self::Card => 'heroicon-o-credit-card',
            self::Voucher => 'heroicon-o-ticket',
            self::Insurance => 'heroicon-o-shield-check',
            self::BankTransfer => 'heroicon-o-building-library',
        };
    }
}
