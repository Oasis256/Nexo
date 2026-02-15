<?php

namespace Modules\NsMultiStore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiStoreApiRoutesExecutedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
