<?php

namespace Modules\RenCommissions\Listeners;

use App\Events\OrderAfterCreatedEvent;
use Modules\RenCommissions\Services\CommissionSettlementService;

class OrderCreatedCommissionListener
{
    public function handle(OrderAfterCreatedEvent $event): void
    {
        app(CommissionSettlementService::class)->settleFromOrder($event->order);
    }
}
