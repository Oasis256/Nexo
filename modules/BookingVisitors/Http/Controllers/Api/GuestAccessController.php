<?php

namespace Modules\BookingVisitors\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\BookingVisitors\Http\Requests\GuestAccessRequest;
use Modules\BookingVisitors\Services\GuestAccessService;

class GuestAccessController extends Controller
{
    public function __construct(private readonly GuestAccessService $guestAccessService)
    {
    }

    public function store(GuestAccessRequest $request)
    {
        $result = $this->guestAccessService->validateByToken(
            token: $request->string('token')->toString(),
            actorId: auth()->id(),
            guestName: $request->string('guest_name')->toString()
        );

        return response()->json($result);
    }
}

