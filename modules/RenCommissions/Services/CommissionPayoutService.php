<?php

namespace Modules\RenCommissions\Services;

use RuntimeException;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Models\CommissionPayout;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Support\StoreContext;

class CommissionPayoutService
{
    public function create(array $commissionIds, ?int $createdBy, ?string $notes = null): CommissionPayout
    {
        $ids = collect($commissionIds)->map(fn ($id) => (int) $id)->filter()->values();
        $storeId = StoreContext::id();

        return DB::transaction(function () use ($ids, $createdBy, $notes, $storeId) {
            $rowsQuery = StoreContext::constrain(
                OrderItemCommission::query()
            )->whereIn('id', $ids)->where('status', 'pending');
            $rows = $rowsQuery->get();

            if ($rows->isEmpty()) {
                throw new RuntimeException(__m('No pending commissions matched this payout request.', 'RenCommissions'));
            }

            $total = $rows->sum('total_commission');

            $payout = CommissionPayout::create([
                'store_id' => $storeId,
                'reference' => 'RC-' . now()->format('Ymd-His'),
                'period_start' => $rows->min('created_at') ?: now(),
                'period_end' => $rows->max('created_at') ?: now(),
                'total_amount' => $total,
                'entries_count' => $rows->count(),
                'status' => 'posted',
                'created_by' => $createdBy,
                'notes' => $notes,
            ]);

            $updateQuery = StoreContext::constrain(
                OrderItemCommission::query()
            )->whereIn('id', $rows->pluck('id'));

            $updateQuery->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => $createdBy,
                    'payout_id' => $payout->id,
                    'updated_at' => now(),
                ]);

            return $payout;
        });
    }
}
