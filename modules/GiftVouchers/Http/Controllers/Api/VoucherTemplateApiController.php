<?php
/**
 * Voucher Template API Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\GiftVouchers\Models\VoucherTemplate;
use Modules\GiftVouchers\Models\VoucherTemplateItem;
use Illuminate\Support\Facades\DB;

class VoucherTemplateApiController extends Controller
{
    /**
     * Get all voucher templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VoucherTemplate::with('items.product');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $templates = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 20));

        return response()->json($templates);
    }

    /**
     * Create a new voucher template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'validity_days' => 'required|integer|min:1',
            'is_transferable' => 'boolean',
            'status' => 'in:active,inactive',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:nexopos_products,id',
            'items.*.unit_id' => 'required|exists:nexopos_units,id',
            'items.*.quantity' => 'required|numeric|min:0.00001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.commission_rate' => 'nullable|numeric|min:0',
            'items.*.commission_type' => 'in:percentage,fixed',
        ]);

        $template = DB::transaction(function () use ($validated) {
            $template = new VoucherTemplate();
            $template->name = $validated['name'];
            $template->description = $validated['description'] ?? null;
            $template->validity_days = $validated['validity_days'];
            $template->is_transferable = $validated['is_transferable'] ?? true;
            $template->status = $validated['status'] ?? 'active';
            $template->author = auth()->id();
            $template->save();

            foreach ($validated['items'] as $itemData) {
                $item = new VoucherTemplateItem();
                $item->template_id = $template->id;
                $item->product_id = $itemData['product_id'];
                $item->unit_id = $itemData['unit_id'];
                $item->quantity = $itemData['quantity'];
                $item->unit_price = $itemData['unit_price'];
                $item->commission_rate = $itemData['commission_rate'] ?? 0;
                $item->commission_type = $itemData['commission_type'] ?? 'percentage';
                $item->save();
            }

            return $template->fresh(['items.product']);
        });

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher template created successfully.'),
            'data' => $template,
        ], 201);
    }

    /**
     * Get a single voucher template.
     */
    public function show(VoucherTemplate $template): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $template->load(['items.product', 'items.unit']),
        ]);
    }

    /**
     * Update a voucher template.
     */
    public function update(Request $request, VoucherTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'validity_days' => 'sometimes|required|integer|min:1',
            'is_transferable' => 'boolean',
            'status' => 'in:active,inactive',
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'nullable|exists:nexopos_gift_voucher_template_items,id',
            'items.*.product_id' => 'required|exists:nexopos_products,id',
            'items.*.unit_id' => 'required|exists:nexopos_units,id',
            'items.*.quantity' => 'required|numeric|min:0.00001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.commission_rate' => 'nullable|numeric|min:0',
            'items.*.commission_type' => 'in:percentage,fixed',
        ]);

        $template = DB::transaction(function () use ($template, $validated) {
            $template->fill(collect($validated)->except('items')->toArray());
            $template->save();

            if (isset($validated['items'])) {
                $existingIds = collect($validated['items'])
                    ->pluck('id')
                    ->filter()
                    ->toArray();

                // Delete removed items
                $template->items()
                    ->whereNotIn('id', $existingIds)
                    ->delete();

                // Update or create items
                foreach ($validated['items'] as $itemData) {
                    if (isset($itemData['id'])) {
                        $item = VoucherTemplateItem::find($itemData['id']);
                        $item->fill($itemData);
                        $item->save();
                    } else {
                        $item = new VoucherTemplateItem();
                        $item->template_id = $template->id;
                        $item->fill($itemData);
                        $item->save();
                    }
                }
            }

            return $template->fresh(['items.product']);
        });

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher template updated successfully.'),
            'data' => $template,
        ]);
    }

    /**
     * Delete a voucher template.
     */
    public function destroy(VoucherTemplate $template): JsonResponse
    {
        // Check if template has vouchers
        if ($template->vouchers()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete template that has issued vouchers.'),
            ], 422);
        }

        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Voucher template deleted successfully.'),
        ]);
    }
}
