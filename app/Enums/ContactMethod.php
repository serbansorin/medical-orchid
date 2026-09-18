<?php

namespace App\Enums;

enum ContactMethod: string
{
    case Sms = 'sms';
    case Email = 'email';
    case Whatsapp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Sms => __('SMS'),
            self::Email => __('Email'),
            self::Whatsapp => __('WhatsApp'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Sms => 'heroicon-o-chat-bubble-left-right',
            self::Email => 'heroicon-o-envelope',
            self::Whatsapp => 'heroicon-o-phone',
        };
    }
}
