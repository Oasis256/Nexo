<?php
/**
 * Voucher Redemption API Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Models\VoucherRedemption;
use Modules\GiftVouchers\Services\VoucherService;
use App\Models\Customer;
use App\Models\Order;

class VoucherRedemptionApiController extends Controller
{
    protected VoucherService $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Redeem voucher items.
     */
    public function redeem(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $request->validate([
            'redeemer_id' => 'nullable|exists:nexopos_users,id',
            'order_id' => 'nullable|exists:nexopos_orders,id',
            'items' => 'required|array|min:1',
            'items.*.voucher_item_id' => 'required|exists:nexopos_gift_voucher_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0.00001',
            'items.*.service_provider_id' => 'nullable|exists:nexopos_users,id',
            'items.*.order_product_id' => 'nullable|exists:nexopos_orders_products,id',
        ]);

        if (!$voucher->isRedeemable()) {
            return response()->json([
                'status' => 'error',
                'message' => __('This voucher cannot be redeemed.'),
                'reason' => $voucher->isExpired() 
                    ? 'expired' 
                    : ($voucher->isFullyRedeemed() ? 'fully_redeemed' : 'invalid_status'),
            ], 422);
        }

        try {
            $redeemer = isset($validated['redeemer_id']) 
                ? Customer::find($validated['redeemer_id']) 
                : null;
            $order = isset($validated['order_id']) 
                ? Order::find($validated['order_id']) 
                : null;

            $redemption = $this->voucherService->redeem(
                $voucher,
                $validated['items'],
                $redeemer,
                $order
            );

            return response()->json([
                'status' => 'success',
                'message' => __('Voucher redeemed successfully.'),
                'data' => [
                    'redemption' => $redemption->load(['items', 'voucher']),
                    'voucher' => $voucher->fresh(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get redemptions for a voucher.
     */
    public function getForVoucher(Voucher $voucher): JsonResponse
    {
        $redemptions = $voucher->redemptions()
            ->with(['items.voucherItem.product', 'redeemer', 'authorUser'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $redemptions,
        ]);
    }
}
