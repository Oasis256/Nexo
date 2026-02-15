<?php

namespace Modules\BookingVisitors\Services;

use Modules\BookingVisitors\Models\BookingGuest;
use Modules\BookingVisitors\Models\VisitEvent;
use Modules\BookingVisitors\Support\StoreContext;

class GuestAccessService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {
    }

    public function validateByToken(string $token, ?int $actorId = null, string $guestName = ''): array
    {
        $tokenRecord = $this->tokenService->resolveBookingToken($token);
        if (! $tokenRecord || ! $tokenRecord->booking) {
            return [
                'status' => 'error',
                'message' => __m('Invalid or expired QR token.', 'BookingVisitors'),
            ];
        }

        $booking = $tokenRecord->booking;
        $isClientPresent = $booking->checked_in_at !== null && $booking->status === 'checked_in';

        if (! $isClientPresent) {
            VisitEvent::query()->create([
                'store_id' => StoreContext::id(),
                'booking_id' => $booking->id,
                'event_type' => 'guest_access_denied',
                'payload' => ['reason' => 'client_not_present'],
                'author' => $actorId,
            ]);

            return [
                'status' => 'error',
                'message' => __m('Guest access denied. Client is not checked in.', 'BookingVisitors'),
            ];
        }

        if (trim($guestName) !== '') {
            BookingGuest::query()->create([
                'store_id' => StoreContext::id(),
                'booking_id' => $booking->id,
                'guest_name' => trim($guestName),
                'guest_phone' => null,
                'status' => 'granted',
                'metadata' => [],
                'author' => $actorId,
            ]);
        }

        VisitEvent::query()->create([
            'store_id' => StoreContext::id(),
            'booking_id' => $booking->id,
            'event_type' => 'guest_access_granted',
            'payload' => ['guest_name' => $guestName],
            'author' => $actorId,
        ]);

        $this->auditService->log('booking.guest_access_granted', 'booking', (int) $booking->id, [
            'guest_name' => $guestName,
        ], $actorId);

        return [
            'status' => 'success',
            'message' => __m('Guest access granted.', 'BookingVisitors'),
            'data' => [
                'booking_uuid' => $booking->uuid,
                'customer_name' => $booking->customer_name,
            ],
        ];
    }
}

