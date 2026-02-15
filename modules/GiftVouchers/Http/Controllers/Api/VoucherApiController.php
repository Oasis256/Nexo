<?php
/**
 * Voucher API Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Models\VoucherTemplate;
use Modules\GiftVouchers\Services\VoucherService;
use Modules\GiftVouchers\Services\VoucherQrCodeService;
use App\Models\Customer;
use App\Models\Order;

class VoucherApiController extends Controller
{
    protected VoucherService $voucherService;
    protected VoucherQrCodeService $qrCodeService;

    public function __construct(
        VoucherService $voucherService,
        VoucherQrCodeService $qrCodeService
    ) {
        $this->voucherService = $voucherService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Get all vouchers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voucher::with(['template', 'purchaser']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('purchaser_id')) {
            $query->where('purchaser_id', $request->input('purchaser_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('template', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('redeemable')) {
            $query->redeemable();
        }

        $vouchers = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 20));

        return response()->json($vouchers);
    }

    /**
     * Create a new voucher from template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:nexopos_gift_voucher_templates,id',
            'purchaser_id' => 'nullable|exists:nexopos_users,id',
            'purchase_order_id' => 'nullable|exists:nexopos_orders,id',
        ]);

        $template = VoucherTemplate::findOrFail($validated['template_id']);
        $purchaser = isset($validated['purchaser_id']) 
            ? Customer::find($validated['purchaser_id']) 
            : null;
        $order = isset($validated['purchase_order_id']) 
            ? Order::find($validated['purchase_order_id']) 
            : null;

        $voucher = $this->voucherService->createFromTemplate($template, $purchaser, $order);

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher created successfully.'),
            'data' => $voucher->load(['items.product', 'template']),
        ], 201);
    }

    /**
     * Get a single voucher.
     */
    public function show(Voucher $voucher): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $voucher->load([
                'template',
                'items.product',
                'purchaser',
                'redemptions.items',
            ]),
        ]);
    }

    /**
     * Update a voucher.
     */
    public function update(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $request->validate([
            'expires_at' => 'nullable|date|after:now',
        ]);

        $voucher->fill($validated);
        $voucher->save();

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher updated successfully.'),
            'data' => $voucher,
        ]);
    }

    /**
     * Delete a voucher.
     */
    public function destroy(Voucher $voucher): JsonResponse
    {
        if ($voucher->redemptions()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete voucher that has been redeemed.'),
            ], 422);
        }

        $this->voucherService->cancel($voucher, 'Deleted by user');

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher deleted successfully.'),
        ]);
    }

    /**
     * Lookup voucher by code.
     */
    public function lookupByCode(string $code): JsonResponse
    {
        $voucher = $this->voucherService->findByCode($code);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => __('Voucher not found.'),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $voucher->load(['items.product', 'template']),
            'is_redeemable' => $voucher->isRedeemable(),
        ]);
    }

    /**
     * Lookup voucher by QR key.
     */
    public function lookupByQrKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_key' => 'required|string',
        ]);

        $voucher = $this->voucherService->findByQrKey($validated['qr_key']);

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => __('Invalid or expired QR code.'),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $voucher->load(['items.product', 'template']),
            'is_redeemable' => $voucher->isRedeemable(),
        ]);
    }

    /**
     * Get QR code for voucher.
     */
    public function getQrCode(Voucher $voucher): JsonResponse
    {
        $qrBase64 = $this->qrCodeService->getQrImageBase64($voucher);
        $qrUrl = $this->qrCodeService->getQrImageUrl($voucher);

        return response()->json([
            'status' => 'success',
            'data' => [
                'base64' => $qrBase64,
                'url' => $qrUrl,
                'redemption_key' => $voucher->qr_redemption_key,
            ],
        ]);
    }

    /**
     * Regenerate QR code for voucher.
     */
    public function regenerateQr(Voucher $voucher): JsonResponse
    {
        $path = $this->qrCodeService->regenerateQr($voucher);

        return response()->json([
            'status' => 'success',
            'message' => __('QR code regenerated successfully.'),
            'data' => [
                'qr_image_path' => $path,
                'base64' => $this->qrCodeService->getQrImageBase64($voucher),
            ],
        ]);
    }

    /**
     * Cancel a voucher.
     */
    public function cancel(Request $request, Voucher $voucher): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $voucher = $this->voucherService->cancel($voucher, $validated['reason'] ?? null);

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher cancelled successfully.'),
            'data' => $voucher,
        ]);
    }

    /**
     * Get voucher statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->voucherService->getStatistics();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Get voucher items formatted for POS cart.
     * This endpoint is used by the POS interface to add voucher items to cart.
     */
    public function getCartItems(Voucher $voucher): JsonResponse
    {
        try {
            $cartData = $this->voucherService->getCartItems($voucher);

            return response()->json([
                'status' => 'success',
                'data' => $cartData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Lookup voucher and get cart items by code or QR key.
     * Combined endpoint for POS scanning.
     */
    public function posLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required_without:qr_key|string|nullable',
            'qr_key' => 'required_without:code|string|nullable',
        ]);

        $voucher = null;

        if (!empty($validated['code'])) {
            $voucher = $this->voucherService->findByCode($validated['code']);
        } elseif (!empty($validated['qr_key'])) {
            $voucher = $this->voucherService->findByQrKey($validated['qr_key']);
        }

        if (!$voucher) {
            return response()->json([
                'status' => 'error',
                'message' => __('Voucher not found.'),
            ], 404);
        }

        if (!$voucher->isRedeemable()) {
            $message = match ($voucher->status) {
                'expired' => __('This voucher has expired.'),
                'fully_redeemed' => __('This voucher has been fully redeemed.'),
                'cancelled' => __('This voucher has been cancelled.'),
                default => __('This voucher cannot be redeemed.'),
            };

            return response()->json([
                'status' => 'error',
                'message' => $message,
                'voucher' => [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'status' => $voucher->status,
                ],
            ], 422);
        }

        try {
            $cartData = $this->voucherService->getCartItems($voucher);
            
            // Format voucher data for the popup
            $voucherData = [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'name' => $voucher->template?->name ?? __('Gift Voucher'),
                'status' => $voucher->status,
                'status_label' => ucfirst($voucher->status),
                'purchaser_name' => $voucher->purchaser?->username ?? __('N/A'),
                'remaining_value' => $voucher->remaining_value,
                'expires_at' => $voucher->expires_at?->format('Y-m-d'),
            ];
            
            // Format items for the popup table
            $items = collect($cartData['items'])->map(function ($item) {
                return [
                    'id' => $item['voucher_item_id'],
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'unit_id' => $item['unit_id'],
                    'unit_name' => $item['unit_name'],
                    'unit_quantity_id' => $item['unit_quantity_id'],
                    'quantity' => $item['quantity'],
                    'remaining_quantity' => $item['quantity_available'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'commission_rate' => $item['commission_rate'] ?? 0,
                    'commission_type' => $item['commission_type'] ?? 'percentage',
                ];
            })->values()->all();

            return response()->json([
                'status' => 'success',
                'voucher' => $voucherData,
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
