<?php
/**
 * Voucher Service
 * Main service for gift voucher operations
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Exceptions\NotFoundException;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Models\VoucherItem;
use Modules\GiftVouchers\Models\VoucherTemplate;
use Modules\GiftVouchers\Models\VoucherRedemption;
use Modules\GiftVouchers\Models\VoucherRedemptionItem;
use Modules\GiftVouchers\Enums\VoucherStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    protected VoucherQrCodeService $qrCodeService;
    protected VoucherAccountingService $accountingService;
    protected VoucherCommissionService $commissionService;

    public function __construct(
        VoucherQrCodeService $qrCodeService,
        VoucherAccountingService $accountingService,
        VoucherCommissionService $commissionService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->accountingService = $accountingService;
        $this->commissionService = $commissionService;
    }

    /**
     * Create a voucher from a template
     *
     * @param VoucherTemplate $template
     * @param Customer|null $purchaser
     * @param Order|null $purchaseOrder
     * @return Voucher
     */
    public function createFromTemplate(
        VoucherTemplate $template,
        ?Customer $purchaser = null,
        ?Order $purchaseOrder = null
    ): Voucher {
        return DB::transaction(function () use ($template, $purchaser, $purchaseOrder) {
            // Create voucher
            $voucher = new Voucher();
            $voucher->template_id = $template->id;
            $voucher->purchaser_id = $purchaser?->id;
            $voucher->purchase_order_id = $purchaseOrder?->id;
            $voucher->total_value = $template->total_value;
            $voucher->remaining_value = $template->total_value;
            $voucher->status = VoucherStatus::ACTIVE->value;
            $voucher->expires_at = now()->addDays($template->validity_days);
            $voucher->author = auth()->id();
            $voucher->save();

            // Copy template items to voucher items
            foreach ($template->items as $templateItem) {
                $voucherItem = new VoucherItem();
                $voucherItem->voucher_id = $voucher->id;
                $voucherItem->template_item_id = $templateItem->id;
                $voucherItem->product_id = $templateItem->product_id;
                $voucherItem->unit_id = $templateItem->unit_id;
                $voucherItem->quantity = $templateItem->quantity;
                $voucherItem->quantity_remaining = $templateItem->quantity;
                $voucherItem->unit_price = $templateItem->unit_price;
                $voucherItem->total_price = $templateItem->total_price;
                $voucherItem->commission_rate = $templateItem->commission_rate;
                $voucherItem->commission_type = $templateItem->commission_type;
                $voucherItem->save();
            }

            // Generate QR code
            $this->qrCodeService->generateQrImage($voucher);

            // Record deferred revenue
            $this->accountingService->recordDeferredRevenue($voucher);

            Log::info('Gift voucher created', [
                'voucher_id' => $voucher->id,
                'code' => $voucher->code,
                'template_id' => $template->id,
                'total_value' => $voucher->total_value,
            ]);

            return $voucher->fresh(['items', 'template']);
        });
    }

    /**
     * Redeem voucher items
     *
     * @param Voucher $voucher
     * @param array $itemsToRedeem Array of ['voucher_item_id' => qty, 'service_provider_id' => user_id]
     * @param Customer|null $redeemer
     * @param Order|null $order
     * @return VoucherRedemption
     */
    public function redeem(
        Voucher $voucher,
        array $itemsToRedeem,
        ?Customer $redeemer = null,
        ?Order $order = null
    ): VoucherRedemption {
        // Validate voucher is redeemable
        if (!$voucher->isRedeemable()) {
            throw new \Exception(__('This voucher cannot be redeemed.'));
        }

        return DB::transaction(function () use ($voucher, $itemsToRedeem, $redeemer, $order) {
            // Create redemption record
            $redemption = new VoucherRedemption();
            $redemption->voucher_id = $voucher->id;
            $redemption->redeemer_id = $redeemer?->id;
            $redemption->redemption_order_id = $order?->id;
            $redemption->author = auth()->id();
            $redemption->save();

            $totalRedeemed = 0;
            $serviceProviderMap = [];

            foreach ($itemsToRedeem as $itemData) {
                $voucherItem = VoucherItem::find($itemData['voucher_item_id']);
                
                if (!$voucherItem || $voucherItem->voucher_id !== $voucher->id) {
                    continue;
                }

                $qtyToRedeem = min(
                    $itemData['quantity'] ?? $voucherItem->quantity_remaining,
                    $voucherItem->quantity_remaining
                );

                if ($qtyToRedeem <= 0) {
                    continue;
                }

                $valueRedeemed = $qtyToRedeem * $voucherItem->unit_price;

                // Create redemption item
                $redemptionItem = new VoucherRedemptionItem();
                $redemptionItem->redemption_id = $redemption->id;
                $redemptionItem->voucher_item_id = $voucherItem->id;
                $redemptionItem->order_product_id = $itemData['order_product_id'] ?? null;
                $redemptionItem->quantity_redeemed = $qtyToRedeem;
                $redemptionItem->value_redeemed = $valueRedeemed;
                $redemptionItem->service_provider_id = $itemData['service_provider_id'] ?? null;
                $redemptionItem->save();

                // Update voucher item remaining quantity
                $voucherItem->quantity_remaining -= $qtyToRedeem;
                $voucherItem->save();

                $totalRedeemed += $valueRedeemed;

                // Track service provider for commission
                if (isset($itemData['service_provider_id'])) {
                    $serviceProviderMap[$voucherItem->id] = $itemData['service_provider_id'];
                }
            }

            // Update redemption total
            $redemption->total_value = $totalRedeemed;
            $redemption->save();

            // Update voucher remaining value and status
            $voucher->remaining_value -= $totalRedeemed;
            
            if ($voucher->remaining_value <= 0) {
                $voucher->status = VoucherStatus::FULLY_REDEEMED->value;
            } else {
                $voucher->status = VoucherStatus::PARTIALLY_REDEEMED->value;
            }
            $voucher->save();

            // Recognize revenue
            $this->accountingService->recognizeRevenue($redemption);

            // Create commissions
            $this->commissionService->createCommissionsForRedemption($redemption, $serviceProviderMap);

            Log::info('Gift voucher redeemed', [
                'voucher_id' => $voucher->id,
                'redemption_id' => $redemption->id,
                'total_redeemed' => $totalRedeemed,
                'remaining_value' => $voucher->remaining_value,
            ]);

            return $redemption->fresh(['items', 'voucher']);
        });
    }

    /**
     * Cancel a voucher
     *
     * @param Voucher $voucher
     * @param string|null $reason
     * @return Voucher
     */
    public function cancel(Voucher $voucher, ?string $reason = null): Voucher
    {
        return DB::transaction(function () use ($voucher, $reason) {
            // Reverse remaining deferred revenue
            $this->accountingService->reverseDeferredRevenue($voucher);

            // Delete QR image
            $this->qrCodeService->deleteQrImage($voucher);

            // Update status
            $voucher->status = VoucherStatus::CANCELLED->value;
            $voucher->qr_redemption_key = null;
            $voucher->save();

            Log::info('Gift voucher cancelled', [
                'voucher_id' => $voucher->id,
                'code' => $voucher->code,
                'remaining_value' => $voucher->remaining_value,
                'reason' => $reason,
            ]);

            return $voucher;
        });
    }

    /**
     * Expire vouchers that have passed their expiry date
     *
     * @return int Number of vouchers expired
     */
    public function expireOverdueVouchers(): int
    {
        $expiredVouchers = Voucher::expired()->get();
        $count = 0;

        foreach ($expiredVouchers as $voucher) {
            DB::transaction(function () use ($voucher) {
                // Reverse remaining deferred revenue
                $this->accountingService->reverseDeferredRevenue($voucher);

                $voucher->status = VoucherStatus::EXPIRED->value;
                $voucher->save();
            });

            $count++;
        }

        if ($count > 0) {
            Log::info('Gift vouchers expired', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Find voucher by code
     *
     * @param string $code
     * @return Voucher|null
     */
    public function findByCode(string $code): ?Voucher
    {
        return Voucher::where('code', $code)->first();
    }

    /**
     * Find voucher by QR redemption key
     *
     * @param string $qrKey
     * @return Voucher|null
     */
    public function findByQrKey(string $qrKey): ?Voucher
    {
        return $this->qrCodeService->validateRedemptionKey($qrKey);
    }

    /**
     * Get vouchers for a customer (purchaser)
     *
     * @param int $customerId
     * @param bool $onlyRedeemable
     * @return Collection
     */
    public function getVouchersForCustomer(int $customerId, bool $onlyRedeemable = false): Collection
    {
        $query = Voucher::forPurchaser($customerId)->with(['items', 'template']);

        if ($onlyRedeemable) {
            $query->redeemable();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Award loyalty points to purchaser
     *
     * @param Voucher $voucher
     * @param float $points
     * @return void
     */
    public function awardPointsToPurchaser(Voucher $voucher, float $points): void
    {
        if (!$voucher->purchaser) {
            return;
        }

        // Update voucher points record
        $voucher->points_awarded = $points;
        $voucher->save();

        // TODO: Integrate with loyalty points system
        // This would call the loyalty points service to add points to the purchaser

        Log::info('Points awarded to voucher purchaser', [
            'voucher_id' => $voucher->id,
            'purchaser_id' => $voucher->purchaser_id,
            'points' => $points,
        ]);
    }

    /**
     * Get voucher summary statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_vouchers' => Voucher::count(),
            'active_vouchers' => Voucher::withStatus(VoucherStatus::ACTIVE)->count(),
            'partially_redeemed' => Voucher::withStatus(VoucherStatus::PARTIALLY_REDEEMED)->count(),
            'fully_redeemed' => Voucher::withStatus(VoucherStatus::FULLY_REDEEMED)->count(),
            'expired' => Voucher::withStatus(VoucherStatus::EXPIRED)->count(),
            'cancelled' => Voucher::withStatus(VoucherStatus::CANCELLED)->count(),
            'total_deferred_revenue' => $this->accountingService->getTotalDeferredRevenue(),
            'total_issued_value' => Voucher::sum('total_value'),
            'total_redeemed_value' => Voucher::sum('total_value') - Voucher::sum('remaining_value'),
        ];
    }

    /**
     * Get voucher items formatted for POS cart integration.
     * Returns items in a format compatible with the POS addToCart method.
     *
     * @param Voucher $voucher
     * @return array
     */
    public function getCartItems(Voucher $voucher): array
    {
        if (!$voucher->isRedeemable()) {
            throw new \Exception(__('This voucher cannot be redeemed.'));
        }

        $voucher->loadMissing([
            'items.product.unit_quantities',
            'items.unit',
            'template',
        ]);

        $cartItems = [];

        foreach ($voucher->items as $item) {
            // Skip fully redeemed items
            if ($item->quantity_remaining <= 0) {
                continue;
            }

            // Find the unit quantity for pricing info
            $unitQuantity = null;
            if ($item->product && $item->product->unit_quantities) {
                $unitQuantity = $item->product->unit_quantities
                    ->where('unit_id', $item->unit_id)
                    ->first();
            }

            $cartItems[] = [
                'voucher_id' => $voucher->id,
                'voucher_code' => $voucher->code,
                'voucher_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product,
                'name' => $item->product?->name ?? __('Voucher Item'),
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '',
                'unit_quantity_id' => $unitQuantity?->id,
                'quantity' => $item->quantity_remaining,
                'quantity_available' => $item->quantity_remaining,
                'unit_price' => $item->unit_price,
                'total_price' => $item->unit_price * $item->quantity_remaining,
                'tax_type' => $item->product?->tax_type ?? 'inclusive',
                'tax_group_id' => $item->product?->tax_group_id,
                'mode' => 'voucher',
                'product_type' => 'voucher_item',
                'commission_rate' => $item->commission_rate,
                'commission_type' => $item->commission_type,
                '$original' => fn() => $item->product ?? (object)[
                    'id' => 0,
                    'name' => __('Voucher Item'),
                    'stock_management' => 'disabled',
                    'category_id' => 0,
                    'tax_group' => null,
                    'tax_type' => 'inclusive',
                ],
            ];
        }

        return [
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'template_name' => $voucher->template?->name,
                'status' => $voucher->status,
                'remaining_value' => $voucher->remaining_value,
                'expires_at' => $voucher->expires_at?->toISOString(),
            ],
            'items' => $cartItems,
        ];
    }
}
