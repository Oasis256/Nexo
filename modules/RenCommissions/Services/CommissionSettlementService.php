<?php

namespace Modules\RenCommissions\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Models\PosCommissionSession;
use Modules\RenCommissions\Support\StoreContext;

class CommissionSettlementService
{
    public function settleFromOrder(Order $order): void
    {
        $storeId = StoreContext::id();

        $sessionRowsQuery = PosCommissionSession::where('session_id', $order->uuid);
        if ($storeId !== null) {
            $sessionRowsQuery->where('store_id', $storeId);
        }
        $sessionRows = $sessionRowsQuery->get();
        if ($sessionRows->isEmpty()) {
            return;
        }

        $products = $order->products()->get()->values();

        DB::transaction(function () use ($sessionRows, $products, $order, $storeId) {
            foreach ($sessionRows as $session) {
                $line = $products->get((int) $session->product_index);
                if ($line === null) {
                    continue;
                }

                $existsQuery = OrderItemCommission::where('order_id', $order->id)
                    ->where('order_product_id', $line->id)
                    ->where('earner_id', $session->earner_id);
                if ($storeId !== null) {
                    $existsQuery->where('store_id', $storeId);
                }

                $exists = $existsQuery->exists();

                if ($exists) {
                    continue;
                }

                OrderItemCommission::create([
                    'store_id' => $storeId,
                    'order_id' => $order->id,
                    'order_product_id' => $line->id,
                    'product_id' => (int) $line->product_id,
                    'earner_id' => (int) $session->earner_id,
                    'type_id' => $session->type_id,
                    'commission_method' => $session->commission_method,
                    'commission_value' => (float) $session->commission_value,
                    'unit_price' => (float) $line->unit_price,
                    'quantity' => (float) $line->quantity,
                    'total_commission' => (float) $session->total_commission,
                    'status' => 'pending',
                    'assigned_by' => (int) $session->assigned_by,
                ]);
            }

            $cleanupQuery = PosCommissionSession::where('session_id', $order->uuid);
            if ($storeId !== null) {
                $cleanupQuery->where('store_id', $storeId);
            }
            $cleanupQuery->delete();
        });
    }

    public function voidByOrder(int $orderId, ?int $voidedBy, string $reason): int
    {
        $query = OrderItemCommission::where('order_id', $orderId)
            ->where('status', 'pending');

        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        return $query->update([
                'status' => 'voided',
                'voided_by' => $voidedBy,
                'voided_at' => now(),
                'void_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
