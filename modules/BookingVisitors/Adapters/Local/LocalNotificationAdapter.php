<?php

namespace Modules\BookingVisitors\Adapters\Local;

use Modules\BookingVisitors\Adapters\WhatsApp\WhatsAppBusinessApiAdapter;
use Modules\BookingVisitors\Contracts\NotificationChannelInterface;

class LocalNotificationAdapter implements NotificationChannelInterface
{
    public function __construct(private readonly WhatsAppBusinessApiAdapter $whatsAppBusinessApi)
    {
    }

    public function send(string $channel, string $recipient, string $message, array $context = []): array
    {
        if ($channel === 'whatsapp_business_api') {
            return $this->whatsAppBusinessApi->send($channel, $recipient, $message, $context);
        }

        return [
            'status' => 'queued',
            'provider' => 'local',
            'recipient' => $recipient,
            'message' => $message,
        ];
    }
}

