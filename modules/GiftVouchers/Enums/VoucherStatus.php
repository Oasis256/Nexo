<?php

namespace Modules\GiftVouchers\Enums;

enum VoucherStatus: string
{
    case ACTIVE = 'active';
    case PARTIALLY_REDEEMED = 'partially_redeemed';
    case FULLY_REDEEMED = 'fully_redeemed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __m('Active', 'GiftVouchers'),
            self::PARTIALLY_REDEEMED => __m('Partially Redeemed', 'GiftVouchers'),
            self::FULLY_REDEEMED => __m('Fully Redeemed', 'GiftVouchers'),
            self::EXPIRED => __m('Expired', 'GiftVouchers'),
            self::CANCELLED => __m('Cancelled', 'GiftVouchers'),
        };
    }

    public function isRedeemable(): bool
    {
        return in_array($this, [self::ACTIVE, self::PARTIALLY_REDEEMED]);
    }
}
