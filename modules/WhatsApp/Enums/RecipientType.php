<?php

namespace Modules\WhatsApp\Enums;

enum RecipientType: string
{
    case CUSTOMER = 'customer';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => __('Customer'),
            self::USER => __('Staff/User'),
        };
    }
}
