<?php

namespace Modules\WhatsApp\Enums;

enum TemplateTarget: string
{
    case CUSTOMER = 'customer';
    case STAFF = 'staff';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => __('Customer Only'),
            self::STAFF => __('Staff Only'),
            self::BOTH => __('Both Customer & Staff'),
        };
    }
}
