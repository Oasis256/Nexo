<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\OrderAfterRefundedEvent;
use Modules\WhatsApp\Services\WhatsAppService;

class OrderRefundListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAfterRefundedEvent $event): void
    {
        $order = $event->order;
        $refund = $event->orderRefund;

        $refundAmount = $refund->total ?? 0;
        $reason = $refund->description ?? '';

        $this->whatsAppService->notifyOrderRefunded($order, $refundAmount, $reason);
    }
}
