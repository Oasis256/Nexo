<?php

namespace Modules\BookingVisitors\Contracts;

interface QrTokenProviderInterface
{
    public function generateToken(int $length = 64): string;

    public function hash(string $token): string;
}

