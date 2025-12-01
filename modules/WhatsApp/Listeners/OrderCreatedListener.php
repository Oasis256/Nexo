<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\OrderAfterCreatedEvent;
use Modules\WhatsApp\Services\WhatsAppService;

class OrderCreatedListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAfterCreatedEvent $event): void
    {
        $this->whatsAppService->notifyOrderCreated($event->order);
    }
}
