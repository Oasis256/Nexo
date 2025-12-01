<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\OrderVoidedEvent;
use App\Models\Order;
use Modules\WhatsApp\Services\WhatsAppService;

class OrderVoidedListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderVoidedEvent $event): void
    {
        $order = $event->order;

        if (ns()->option->get('whatsapp_enabled', 'no') !== 'yes') {
            return;
        }

        $customer = $order->customer;
        if (!$customer || empty($customer->phone)) {
            return;
        }

        $this->whatsAppService->sendToCustomer(
            customer: $customer,
            templateName: 'order_voided',
            additionalData: $this->whatsAppService->buildOrderData($order),
            relatedType: Order::class,
            relatedId: $order->id
        );
    }
}
