<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\OrderAfterUpdatedDeliveryStatus;
use Modules\WhatsApp\Services\WhatsAppService;

class DeliveryStatusListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAfterUpdatedDeliveryStatus $event): void
    {
        $this->whatsAppService->notifyDeliveryUpdate($event->order);
    }
}
