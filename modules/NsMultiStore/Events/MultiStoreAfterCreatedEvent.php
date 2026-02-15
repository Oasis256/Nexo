<?php
namespace Modules\NsMultiStore\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\NsMultiStore\Models\Store;

/**
 * Register Event
**/
class MultiStoreAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct( public Store $store )
    {
        // ...
    }
}