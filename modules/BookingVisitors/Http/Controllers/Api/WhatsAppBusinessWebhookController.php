<?php

namespace Modules\BookingVisitors\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\BookingVisitors\Services\BookingService;

class WhatsAppBusinessWebhookController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function verify(Request $request)
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));
        $expectedToken = (string) ns()->option->get('bookingvisitors_whatsapp_verify_token', '');

        if ($mode === 'subscribe' && $token !== '' && $expectedToken !== '' && hash_equals($expectedToken, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'status' => 'error',
            'message' => __m('Webhook verification failed.', 'BookingVisitors'),
        ], 403);
    }

    public function receive(Request $request)
    {
        $booking = $this->bookingService->createFromWhatsAppBusinessWebhook($request->all(), auth()->id());

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking_id' => $booking?->id,
                'booking_uuid' => $booking?->uuid,
            ],
        ]);
    }
}

