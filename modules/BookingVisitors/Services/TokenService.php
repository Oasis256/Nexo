<?php

namespace Modules\BookingVisitors\Services;

use Carbon\Carbon;
use Modules\BookingVisitors\Contracts\QrTokenProviderInterface;
use Modules\BookingVisitors\Models\Booking;
use Modules\BookingVisitors\Models\QrToken;
use Modules\BookingVisitors\Support\StoreContext;

class TokenService
{
    public function __construct(private readonly QrTokenProviderInterface $tokenProvider)
    {
    }

    public function issueBookingToken(Booking $booking, ?int $actorId = null): array
    {
        $token = $this->tokenProvider->generateToken();
        $ttlHours = (int) ns()->option->get('bookingvisitors_qr_token_ttl_hours', 24);
        $expiresAt = Carbon::now()->addHours(max($ttlHours, 1));

        $record = QrToken::query()->create([
            'store_id' => StoreContext::id(),
            'booking_id' => $booking->id,
            'scope' => 'booking',
            'token_hash' => $this->tokenProvider->hash($token),
            'expires_at' => $expiresAt,
            'used_at' => null,
            'used_by' => null,
            'revoked_at' => null,
            'metadata' => [],
            'author' => $actorId,
        ]);

        return [
            'token' => $token,
            'record' => $record,
        ];
    }

    public function resolveBookingToken(string $token): ?QrToken
    {
        $tokenHash = $this->tokenProvider->hash($token);
        $query = QrToken::query()->where('scope', 'booking')->where('token_hash', $tokenHash)->whereNull('revoked_at');
        StoreContext::apply($query);

        $record = $query->latest('id')->first();

        if (! $record) {
            return null;
        }

        if ($record->expires_at && now()->greaterThan($record->expires_at)) {
            return null;
        }

        return $record;
    }
}

