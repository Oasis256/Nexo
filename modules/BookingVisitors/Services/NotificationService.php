<?php

namespace Modules\BookingVisitors\Services;

use Modules\BookingVisitors\Contracts\NotificationChannelInterface;
use Modules\BookingVisitors\Models\Booking;
use Modules\BookingVisitors\Models\ChannelMessage;
use Modules\BookingVisitors\Support\StoreContext;

class NotificationService
{
    public function __construct(private readonly NotificationChannelInterface $notificationChannel)
    {
    }

    public function sendBookingConfirmation(Booking $booking, string $token, ?int $actorId = null): array
    {
        $channel = $booking->channel === 'whatsapp_business_api'
            ? 'whatsapp_business_api'
            : 'local';

        $recipient = (string) ($booking->customer_phone ?: $booking->customer_email ?: '');
        $message = sprintf(
            'Booking %s confirmed. Your check-in token: %s',
            $booking->uuid,
            $token
        );

        $result = $this->notificationChannel->send($channel, $recipient, $message, [
            'booking_id' => $booking->id,
        ]);

        ChannelMessage::query()->create([
            'store_id' => StoreContext::id(),
            'booking_id' => $booking->id,
            'channel' => $channel,
            'message_type' => 'booking_confirmation',
            'recipient' => $recipient,
            'status' => (string) ($result['status'] ?? 'queued'),
            'provider_ref' => (string) ($result['payload']['messages'][0]['id'] ?? ''),
            'payload' => [
                'request' => [
                    'message' => $message,
                ],
                'response' => $result,
            ],
            'author' => $actorId,
        ]);

        return $result;
    }
}

