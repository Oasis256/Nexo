<?php

namespace Modules\Commission\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Commission\Models\EarnedCommission;

class CommissionAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public EarnedCommission $earnedCommission;

    /**
     * Create a new event instance.
     */
    public function __construct(EarnedCommission $earnedCommission)
    {
        $this->earnedCommission = $earnedCommission;
    }
}
