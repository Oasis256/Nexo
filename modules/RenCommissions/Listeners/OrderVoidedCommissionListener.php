<?php

namespace Modules\RenCommissions\Listeners;

use App\Events\OrderVoidedEvent;
use Modules\RenCommissions\Services\CommissionSettlementService;

class OrderVoidedCommissionListener
{
    public function handle(OrderVoidedEvent $event): void
    {
        app(CommissionSettlementService::class)->voidByOrder($event->order->id, auth()->id(), __m('Order voided', 'RenCommissions'));
    }
}
