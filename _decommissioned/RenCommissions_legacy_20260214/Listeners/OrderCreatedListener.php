<?php

namespace Modules\RenCommissions\Listeners;

use App\Events\OrderAfterCreatedEvent;
use Illuminate\Support\Facades\Log;
use Modules\RenCommissions\Services\PerItemCommissionService;

/**
 * Order Created Listener
 * 
 * Converts session commissions to permanent records when order is finalized.
 */
class OrderCreatedListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderAfterCreatedEvent $event): void
    {
        try {
            $order = $event->order;
            $orderProducts = $order->products ?? collect();

            // Prepare order products array with index mapping
            $productsArray = $orderProducts->map(function ($product, $index) {
                return [
                    'id' => $product->id,
                    'index' => $index,
                    'product_id' => $product->product_id,
                    'quantity' => $product->quantity,
                    'unit_price' => $product->unit_price,
                ];
            })->toArray();

            // Convert session commissions to order commissions
            $service = app()->make(PerItemCommissionService::class);
            $created = $service->convertSessionCommissions($order->id, $productsArray);

            if ($created->isNotEmpty()) {
                Log::info('RenCommissions: Converted ' . $created->count() . ' session commissions for order #' . $order->id);
            }

        } catch (\Exception $e) {
            Log::error('RenCommissions: Error converting session commissions', [
                'order_id' => $event->order->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
