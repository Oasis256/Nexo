<?php

namespace Modules\BookingVisitors\Adapters\Local;

use Modules\BookingVisitors\Contracts\ChannelBookingSourceInterface;

class LocalBookingSourceAdapter implements ChannelBookingSourceInterface
{
    public function normalize(array $payload): array
    {
        return [
            'channel' => (string) ($payload['channel'] ?? 'phone'),
            'customer_name' => (string) ($payload['customer_name'] ?? ''),
            'customer_phone' => (string) ($payload['customer_phone'] ?? ''),
            'customer_email' => (string) ($payload['customer_email'] ?? ''),
            'start_at' => (string) ($payload['start_at'] ?? ''),
            'end_at' => (string) ($payload['end_at'] ?? ''),
            'notes' => (string) ($payload['notes'] ?? ''),
            'metadata' => is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        ];
    }
}

