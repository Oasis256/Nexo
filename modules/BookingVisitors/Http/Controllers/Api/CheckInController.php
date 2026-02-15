<?php

namespace Modules\BookingVisitors\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\BookingVisitors\Http\Requests\CheckInRequest;
use Modules\BookingVisitors\Services\CheckInService;

class CheckInController extends Controller
{
    public function __construct(private readonly CheckInService $checkInService)
    {
    }

    public function store(CheckInRequest $request)
    {
        $result = $this->checkInService->checkInByToken(
            token: $request->string('token')->toString(),
            actorId: auth()->id(),
            source: $request->string('source', 'qr_scanner')->toString()
        );

        return response()->json($result);
    }
}

