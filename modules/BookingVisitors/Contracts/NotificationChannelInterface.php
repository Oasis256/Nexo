<?php

namespace Modules\BookingVisitors\Contracts;

interface NotificationChannelInterface
{
    public function send(string $channel, string $recipient, string $message, array $context = []): array;
}

