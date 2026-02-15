<?php

namespace Modules\WhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppWebhookReceivedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $payload,
        public string $type
    ) {}
}
