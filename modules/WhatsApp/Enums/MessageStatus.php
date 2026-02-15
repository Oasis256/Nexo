<?php

namespace Modules\WhatsApp\Enums;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::SENT => __('Sent'),
            self::DELIVERED => __('Delivered'),
            self::READ => __('Read'),
            self::FAILED => __('Failed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SENT => 'info',
            self::DELIVERED => 'success',
            self::READ => 'success',
            self::FAILED => 'error',
        };
    }
}
