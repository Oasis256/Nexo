<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\CustomerAfterCreatedEvent;
use Modules\WhatsApp\Services\WhatsAppService;

class CustomerCreatedListener
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CustomerAfterCreatedEvent $event): void
    {
        if (ns()->option->get('whatsapp_send_welcome_message', 'yes') !== 'yes') {
            return;
        }

        if (ns()->option->get('whatsapp_enabled', 'no') !== 'yes') {
            return;
        }

        $customer = $event->customer;

        if (empty($customer->phone)) {
            return;
        }

        $this->whatsAppService->sendToCustomer(
            customer: $customer,
            templateName: 'welcome_customer'
        );
    }
}
