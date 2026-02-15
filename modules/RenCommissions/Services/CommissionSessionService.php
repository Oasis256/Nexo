<?php

namespace Modules\RenCommissions\Services;

use Modules\RenCommissions\Models\PosCommissionSession;
use Modules\RenCommissions\Support\StoreContext;

class CommissionSessionService
{
    public function assign(array $payload): PosCommissionSession
    {
        $storeId = StoreContext::id();

        return PosCommissionSession::updateOrCreate(
            [
                'store_id' => $storeId,
                'session_id' => $payload['session_id'],
                'product_index' => $payload['product_index'],
            ],
            [
                'store_id' => $storeId,
                'product_id' => $payload['product_id'],
                'earner_id' => $payload['earner_id'],
                'type_id' => $payload['type_id'] ?? null,
                'commission_method' => $payload['commission_method'],
                'commission_value' => $payload['commission_value'],
                'unit_price' => $payload['unit_price'],
                'quantity' => $payload['quantity'],
                'total_commission' => $payload['total_commission'],
                'assigned_by' => $payload['assigned_by'],
            ]
        );
    }

    public function remove(string $sessionId, int $productIndex): int
    {
        $storeId = StoreContext::id();

        $query = PosCommissionSession::where('session_id', $sessionId)
            ->where('product_index', $productIndex);

        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        return $query->delete();
    }

    public function clear(string $sessionId): int
    {
        $query = PosCommissionSession::where('session_id', $sessionId);
        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('store_id', $storeId);
        }

        return $query->delete();
    }
}
