<?php

namespace Modules\BookingVisitors\Contracts;

interface ChannelBookingSourceInterface
{
    public function normalize(array $payload): array;
}

