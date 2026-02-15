<?php

namespace Modules\BookingVisitors\Adapters\Local;

use Illuminate\Support\Str;
use Modules\BookingVisitors\Contracts\QrTokenProviderInterface;

class LocalQrTokenProvider implements QrTokenProviderInterface
{
    public function generateToken(int $length = 64): string
    {
        return Str::random($length);
    }

    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}

