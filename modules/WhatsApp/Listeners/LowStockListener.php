<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\LowStockProductsCountedEvent;
use Modules\WhatsApp\Services\WhatsAppService;

class LowStockListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(LowStockProductsCountedEvent $event): void
    {
        $products = $event->products ?? [];

        if (empty($products)) {
            return;
        }

        $this->whatsAppService->notifyLowStock($products);
    }
}
