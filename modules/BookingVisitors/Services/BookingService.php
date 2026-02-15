<?php

namespace Modules\BookingVisitors\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\BookingVisitors\Contracts\ChannelBookingSourceInterface;
use Modules\BookingVisitors\Models\Booking;
use Modules\BookingVisitors\Models\BookingGuest;
use Modules\BookingVisitors\Support\StoreContext;

class BookingService
{
    public function __construct(
        private readonly ChannelBookingSourceInterface $bookingSource,
        private readonly TokenService $tokenService,
        private readonly NotificationService $notificationService,
        private readonly AuditService $auditService
    ) {
    }

    public function createFromChannel(array $payload, ?int $actorId = null): Booking
    {
        $data = $this->bookingSource->normalize($payload);

        $booking = Booking::query()->create([
            'store_id' => StoreContext::id(),
            'uuid' => strtoupper(Str::random(10)),
            'channel' => (string) ($data['channel'] ?? 'phone'),
            'status' => 'confirmed',
            'customer_name' => (string) ($data['customer_name'] ?? ''),
            'customer_phone' => (string) ($data['customer_phone'] ?? ''),
            'customer_email' => (string) ($data['customer_email'] ?? ''),
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'confirmed_at' => now(),
            'checked_in_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
            'notes' => (string) ($data['notes'] ?? ''),
            'metadata' => Arr::wrap($data['metadata'] ?? []),
            'author' => $actorId,
        ]);

        foreach ((array) ($data['guests'] ?? []) as $guest) {
            if (! is_array($guest)) {
                continue;
            }

            BookingGuest::query()->create([
                'store_id' => StoreContext::id(),
                'booking_id' => $booking->id,
                'guest_name' => (string) ($guest['name'] ?? ''),
                'guest_phone' => (string) ($guest['phone'] ?? ''),
                'status' => 'pending',
                'metadata' => [],
                'author' => $actorId,
            ]);
        }

        $issuedToken = $this->tokenService->issueBookingToken($booking, $actorId);
        $this->notificationService->sendBookingConfirmation($booking, $issuedToken['token'], $actorId);

        $this->auditService->log('booking.created', 'booking', (int) $booking->id, [
            'channel' => $booking->channel,
            'customer_name' => $booking->customer_name,
        ], $actorId);

        $booking->setAttribute('check_in_token', $issuedToken['token']);

        return $booking;
    }

    public function createFromWhatsAppBusinessWebhook(array $payload, ?int $actorId = null): ?Booking
    {
        $entry = data_get($payload, 'entry.0.changes.0.value.messages.0', []);
        $from = (string) data_get($entry, 'from', '');
        $text = trim((string) data_get($entry, 'text.body', ''));

        if ($from === '' || $text === '') {
            return null;
        }

        $parts = preg_split('/\s*\|\s*/', $text);
        $customerName = (string) ($parts[0] ?? 'WhatsApp Customer');
        $startAt = (string) ($parts[1] ?? now()->addHour()->toDateTimeString());
        $endAt = (string) ($parts[2] ?? now()->addHours(2)->toDateTimeString());

        return $this->createFromChannel([
            'channel' => 'whatsapp_business_api',
            'customer_name' => $customerName,
            'customer_phone' => $from,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'notes' => 'Created from WhatsApp Business API webhook.',
            'metadata' => [
                'source' => 'whatsapp_business_api',
                'raw' => $payload,
            ],
        ], $actorId);
    }
}
