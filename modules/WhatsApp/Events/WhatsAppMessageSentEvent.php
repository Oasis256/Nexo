<?php

namespace Modules\WhatsApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\WhatsApp\Models\MessageLog;

class WhatsAppMessageSentEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MessageLog $messageLog
    ) {}
}
