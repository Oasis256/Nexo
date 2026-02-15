<?php

namespace Modules\NsMultiStore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiStoreWebRoutesLoadedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
}
