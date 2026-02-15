<?php

namespace Modules\GiftVouchers\Enums;

enum CommissionType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => __m('Percentage', 'GiftVouchers'),
            self::FIXED => __m('Fixed Amount', 'GiftVouchers'),
        };
    }
}
