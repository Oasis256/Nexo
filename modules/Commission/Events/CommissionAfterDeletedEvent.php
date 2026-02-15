<?php

namespace Modules\Commission\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Commission\Models\EarnedCommission;

class CommissionAfterDeletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $orderId;
    public int $deletedCount;

    /**
     * Create a new event instance.
     */
    public function __construct(int $orderId, int $deletedCount)
    {
        $this->orderId = $orderId;
        $this->deletedCount = $deletedCount;
    }
}
