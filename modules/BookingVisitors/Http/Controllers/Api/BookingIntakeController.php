<?php

namespace Modules\BookingVisitors\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\BookingVisitors\Http\Requests\BookingIntakeRequest;
use Modules\BookingVisitors\Services\BookingService;

class BookingIntakeController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function store(BookingIntakeRequest $request)
    {
        $booking = $this->bookingService->createFromChannel($request->validated(), auth()->id());

        return response()->json([
            'status' => 'success',
            'data' => [
                'booking_id' => $booking->id,
                'booking_uuid' => $booking->uuid,
                'status' => $booking->status,
            ],
            'message' => __m('Booking created successfully.', 'BookingVisitors'),
        ]);
    }
}

