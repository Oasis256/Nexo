<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\OrderPaymentAfterCreatedEvent;
use Modules\WhatsApp\Services\WhatsAppService;

class OrderPaymentListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderPaymentAfterCreatedEvent $event): void
    {
        $payment = $event->orderPayment;
        $order = $payment->order;

        if (!$order) {
            return;
        }

        $this->whatsAppService->notifyPaymentReceived($order, $payment);
    }
}
