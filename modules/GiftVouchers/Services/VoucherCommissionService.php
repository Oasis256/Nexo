<?php
/**
 * Voucher Commission Service
 * Handles commission calculation and distribution for gift voucher redemptions
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Services;

use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Models\VoucherItem;
use Modules\GiftVouchers\Models\VoucherRedemption;
use Modules\GiftVouchers\Models\VoucherRedemptionItem;
use Modules\GiftVouchers\Models\VoucherCommission;
use Modules\GiftVouchers\Enums\CommissionType;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VoucherCommissionService
{
    /**
     * Calculate and create commission for a redemption item
     *
     * @param VoucherRedemptionItem $redemptionItem
     * @param int|null $serviceProviderId User ID of the service provider
     * @return VoucherCommission|null
     */
    public function createCommission(
        VoucherRedemptionItem $redemptionItem,
        ?int $serviceProviderId = null
    ): ?VoucherCommission {
        $voucherItem = $redemptionItem->voucherItem;
        
        if (!$voucherItem) {
            return null;
        }

        // No commission rate defined
        if ($voucherItem->commission_rate <= 0) {
            return null;
        }

        // Determine service provider
        $providerId = $serviceProviderId ?? $redemptionItem->service_provider_id;
        
        if (!$providerId) {
            Log::warning('No service provider for commission', [
                'redemption_item_id' => $redemptionItem->id,
            ]);
            return null;
        }

        // Calculate commission
        $commissionValue = $this->calculateCommission(
            baseAmount: $redemptionItem->value_redeemed,
            rate: $voucherItem->commission_rate,
            type: CommissionType::from($voucherItem->commission_type)
        );

        if ($commissionValue <= 0) {
            return null;
        }

        $redemption = $redemptionItem->redemption;
        $voucher = $redemption->voucher;

        $commission = new VoucherCommission();
        $commission->redemption_item_id = $redemptionItem->id;
        $commission->voucher_id = $voucher->id;
        $commission->order_id = $redemption->redemption_order_id;
        $commission->order_product_id = $redemptionItem->order_product_id;
        $commission->product_id = $voucherItem->product_id;
        $commission->user_id = $providerId;
        $commission->base_amount = $redemptionItem->value_redeemed;
        $commission->commission_rate = $voucherItem->commission_rate;
        $commission->commission_type = $voucherItem->commission_type;
        $commission->value = $commissionValue;
        $commission->author = auth()->id();
        $commission->save();

        Log::info('Voucher commission created', [
            'commission_id' => $commission->id,
            'user_id' => $providerId,
            'value' => $commissionValue,
        ]);

        return $commission;
    }

    /**
     * Create commissions for all items in a redemption
     *
     * @param VoucherRedemption $redemption
     * @param array $serviceProviderMap Optional mapping of voucher_item_id => user_id
     * @return Collection<VoucherCommission>
     */
    public function createCommissionsForRedemption(
        VoucherRedemption $redemption,
        array $serviceProviderMap = []
    ): Collection {
        $commissions = collect();

        foreach ($redemption->items as $redemptionItem) {
            $providerId = $serviceProviderMap[$redemptionItem->voucher_item_id] 
                ?? $redemptionItem->service_provider_id 
                ?? null;

            $commission = $this->createCommission($redemptionItem, $providerId);
            
            if ($commission) {
                $commissions->push($commission);
            }
        }

        return $commissions;
    }

    /**
     * Calculate commission amount
     *
     * @param float $baseAmount The amount to calculate commission on
     * @param float $rate The commission rate
     * @param CommissionType $type The type of commission
     * @return float
     */
    public function calculateCommission(float $baseAmount, float $rate, CommissionType $type): float
    {
        if ($type === CommissionType::PERCENTAGE) {
            return round($baseAmount * ($rate / 100), 5);
        }

        // Fixed amount
        return round($rate, 5);
    }

    /**
     * Get total commissions earned by a user
     *
     * @param int $userId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return float
     */
    public function getTotalCommissionsForUser(
        int $userId,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): float {
        $query = VoucherCommission::forUser($userId);

        if ($startDate && $endDate) {
            $query->inDateRange($startDate, $endDate);
        }

        return $query->sum('value');
    }

    /**
     * Get commissions breakdown for a user
     *
     * @param int $userId
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return Collection
     */
    public function getCommissionsBreakdownForUser(
        int $userId,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null
    ): Collection {
        $query = VoucherCommission::forUser($userId)
            ->with(['voucher', 'product', 'order']);

        if ($startDate && $endDate) {
            $query->inDateRange($startDate, $endDate);
        }

        return $query->get();
    }

    /**
     * Get commissions for a voucher
     *
     * @param int $voucherId
     * @return Collection
     */
    public function getCommissionsForVoucher(int $voucherId): Collection
    {
        return VoucherCommission::forVoucher($voucherId)
            ->with(['user', 'product'])
            ->get();
    }

    /**
     * Get commissions for an order
     *
     * @param int $orderId
     * @return Collection
     */
    public function getCommissionsForOrder(int $orderId): Collection
    {
        return VoucherCommission::forOrder($orderId)
            ->with(['user', 'product', 'voucher'])
            ->get();
    }

    /**
     * Get total commissions for a period
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return float
     */
    public function getTotalCommissionsInPeriod(\DateTime $startDate, \DateTime $endDate): float
    {
        return VoucherCommission::inDateRange($startDate, $endDate)->sum('value');
    }

    /**
     * Get commissions grouped by user for a period
     *
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return Collection
     */
    public function getCommissionsByUserInPeriod(\DateTime $startDate, \DateTime $endDate): Collection
    {
        return VoucherCommission::inDateRange($startDate, $endDate)
            ->selectRaw('user_id, SUM(value) as total_commission, COUNT(*) as commission_count')
            ->groupBy('user_id')
            ->with('user:id,username')
            ->get();
    }

    /**
     * Delete commissions for a redemption (for reversals)
     *
     * @param VoucherRedemption $redemption
     * @return int Number of deleted records
     */
    public function deleteCommissionsForRedemption(VoucherRedemption $redemption): int
    {
        $count = VoucherCommission::whereIn(
            'redemption_item_id',
            $redemption->items->pluck('id')
        )->delete();

        Log::info('Voucher commissions deleted for redemption reversal', [
            'redemption_id' => $redemption->id,
            'deleted_count' => $count,
        ]);

        return $count;
    }
}
