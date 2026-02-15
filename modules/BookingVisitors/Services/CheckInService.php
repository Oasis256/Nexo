<?php

namespace Modules\BookingVisitors\Services;

use Modules\BookingVisitors\Models\VisitEvent;
use Modules\BookingVisitors\Support\StoreContext;

class CheckInService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {
    }

    public function checkInByToken(string $token, ?int $actorId = null, string $source = 'qr_scanner'): array
    {
        $tokenRecord = $this->tokenService->resolveBookingToken($token);
        if (! $tokenRecord) {
            return [
                'status' => 'error',
                'message' => __m('Invalid or expired QR token.', 'BookingVisitors'),
            ];
        }

        $booking = $tokenRecord->booking;
        if (! $booking) {
            return [
                'status' => 'error',
                'message' => __m('Booking not found for this token.', 'BookingVisitors'),
            ];
        }

        if ($booking->checked_in_at === null) {
            $booking->checked_in_at = now();
            $booking->status = 'checked_in';
            $booking->save();
        }

        $tokenRecord->used_at = now();
        $tokenRecord->used_by = $actorId;
        $tokenRecord->save();

        VisitEvent::query()->create([
            'store_id' => StoreContext::id(),
            'booking_id' => $booking->id,
            'event_type' => 'check_in',
            'payload' => ['source' => $source],
            'author' => $actorId,
        ]);

        $this->auditService->log('booking.check_in', 'booking', (int) $booking->id, [
            'source' => $source,
        ], $actorId);

        return [
            'status' => 'success',
            'message' => __m('Check-in confirmed.', 'BookingVisitors'),
            'data' => [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'customer_name' => $booking->customer_name,
                'checked_in_at' => optional($booking->checked_in_at)->toDateTimeString(),
            ],
        ];
    }
}

