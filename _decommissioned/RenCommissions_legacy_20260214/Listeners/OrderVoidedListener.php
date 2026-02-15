<?php

namespace Modules\RenCommissions\Listeners;

use App\Events\OrderVoidedEvent;
use Illuminate\Support\Facades\Log;
use Modules\RenCommissions\Services\PerItemCommissionService;

/**
 * Order Voided Listener
 * 
 * Voids all commissions when order is voided.
 */
class OrderVoidedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderVoidedEvent $event): void
    {
        try {
            $order = $event->order;

            // Void all commissions for this order
            $service = app()->make(PerItemCommissionService::class);
            $voided = $service->voidOrderCommissions($order->id, __m('Order voided', 'RenCommissions'));

            if ($voided > 0) {
                Log::info('RenCommissions: Voided ' . $voided . ' commissions for order #' . $order->id);
            }

        } catch (\Exception $e) {
            Log::error('RenCommissions: Error voiding commissions', [
                'order_id' => $event->order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
