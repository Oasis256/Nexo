<?php

namespace Modules\NsCommissions\Events;

use App\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\NsCommissions\Models\EarnedCommission;

class CommissionAfterCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $earnedCommission;

    public function __construct(EarnedCommission $commission)
    {
        $this->earnedCommission = $commission;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ns.private-channel');
    }
}
