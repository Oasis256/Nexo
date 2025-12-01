<?php

namespace Modules\GiftVouchers\Enums;

enum TemplateStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __m('Active', 'GiftVouchers'),
            self::INACTIVE => __m('Inactive', 'GiftVouchers'),
        };
    }
}
