<?php

namespace Modules\Commission\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\CommissionAssignment;
use Modules\Commission\Models\CommissionProductValue;
use Modules\Commission\Models\EarnedCommission;
use Modules\Commission\Services\CommissionCalculatorService;
use Modules\Commission\Services\CommissionReportService;

/**
 * API Controller for Commission operations
 */
class CommissionApiController extends Controller
{
    public function __construct(
        protected CommissionCalculatorService $calculatorService,
        protected CommissionReportService $reportService
    ) {
    }

    /**
     * Preview commission calculation for a product
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|exists:nexopos_products,id',
            'category_id' => 'required|exists:nexopos_products_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0.01',
            'user_id' => 'required|exists:nexopos_users,id',
        ]);

        $preview = $this->calculatorService->previewCommission(
            productId: $request->product_id ? (int) $request->product_id : null,
            productCategoryId: (int) $request->category_id,
            unitPrice: (float) $request->unit_price,
            quantity: (float) $request->quantity,
            userId: (int) $request->user_id
        );

        return response()->json([
            'status' => 'success',
            'data' => $preview,
        ]);
    }

    /**
     * Get statistics for the widget
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');

        // Calculate date ranges based on period
        $now = now();
        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $prevStartDate = $now->copy()->subDay()->startOfDay();
                $prevEndDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $prevStartDate = $now->copy()->subWeek()->startOfWeek();
                $prevEndDate = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $prevStartDate = $now->copy()->subYear()->startOfYear();
                $prevEndDate = $now->copy()->subYear()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $prevStartDate = $now->copy()->subMonth()->startOfMonth();
                $prevEndDate = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        // Get current period stats
        $currentTotal = EarnedCommission::whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
        $currentCount = EarnedCommission::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Get previous period stats for comparison
        $previousTotal = EarnedCommission::whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('amount');

        // Calculate percent change
        $percentChange = null;
        if ($previousTotal > 0) {
            $percentChange = (($currentTotal - $previousTotal) / $previousTotal) * 100;
        } elseif ($currentTotal > 0) {
            $percentChange = 100;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_amount' => $currentTotal,
                'count' => $currentCount,
                'percent_change' => $percentChange,
                'period' => $period,
            ],
        ]);
    }

    /**
     * Get active commissions for a product
     */
    public function getProductCommissions(Product $product): JsonResponse
    {
        $commissions = Commission::active()
            ->where(function ($query) use ($product) {
                $query->whereDoesntHave('categories')
                    ->orWhereHas('categories', function ($q) use ($product) {
                        $q->where('nexopos_products_categories.id', $product->category_id);
                    });
            })
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'name' => $commission->name,
                    'type' => $commission->type,
                    'type_label' => $commission->type_label,
                    'value' => $commission->value,
                    'formatted_value' => $commission->formatted_value,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $commissions,
        ]);
    }

    /**
     * Get user commission summary
     */
    public function getUserSummary(User $user, Request $request): JsonResponse
    {
        ns()->restrict(['commission.reports']);

        $startDate = $request->get('start_date') 
            ? \Carbon\Carbon::parse($request->get('start_date'))
            : now()->startOfMonth();
        $endDate = $request->get('end_date') 
            ? \Carbon\Carbon::parse($request->get('end_date'))
            : now()->endOfDay();

        $summary = $this->reportService->getUserCommissionSummary(
            startDate: $startDate,
            endDate: $endDate,
            userId: $user->id
        );

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * Get top earners
     */
    public function getTopEarners(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $period = $request->get('period', 'month');

        // Calculate date range based on period
        $now = now();
        switch ($period) {
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            case 'month':
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        $topEarners = $this->reportService->getTopEarners(
            startDate: $startDate,
            endDate: $endDate,
            limit: $limit
        );

        return response()->json([
            'status' => 'success',
            'data' => $topEarners,
        ]);
    }

    /**
     * Get recent commissions
     */
    public function getRecentCommissions(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 10), 50);

        $recentCommissions = $this->reportService->getRecentCommissions($limit);

        return response()->json([
            'status' => 'success',
            'data' => $recentCommissions,
        ]);
    }

    /**
     * Get daily earnings for chart
     */
    public function getDailyEarnings(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date') 
            ? \Carbon\Carbon::parse($request->get('start_date')) 
            : now()->subDays(30)->startOfDay();
        $endDate = $request->get('end_date') 
            ? \Carbon\Carbon::parse($request->get('end_date'))
            : now()->endOfDay();

        $dailyEarnings = $this->reportService->getDailyEarnings(
            startDate: $startDate,
            endDate: $endDate
        );

        return response()->json([
            'status' => 'success',
            'data' => $dailyEarnings,
        ]);
    }

    /**
     * Get product values for a commission
     */
    public function getProductValues(Commission $commission): JsonResponse
    {
        $productValues = CommissionProductValue::where('commission_id', $commission->id)
            ->with('product:id,name,sku')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $productValues,
        ]);
    }

    /**
     * Save product values for a commission
     */
    public function saveProductValues(Commission $commission, Request $request): JsonResponse
    {
        ns()->restrict(['commission.update']);

        $request->validate([
            'product_values' => 'required|array',
            'product_values.*.product_id' => 'required|exists:nexopos_products,id',
            'product_values.*.value' => 'required|numeric|min:0',
        ]);

        // Delete existing values for this commission
        CommissionProductValue::where('commission_id', $commission->id)->delete();

        // Insert new values
        foreach ($request->product_values as $pv) {
            CommissionProductValue::create([
                'commission_id' => $commission->id,
                'product_id' => $pv['product_id'],
                'value' => $pv['value'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Product values saved successfully.'),
        ]);
    }

    /**
     * Search products for product values
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->get('search', '');

        if (strlen($search) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $products = Product::where('type', '!=', 'grouped')
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'sku', 'barcode']);

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    /**
     * Store commission assignments from POS
     */
    public function storeAssignments(Request $request): JsonResponse
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.order_id' => 'required|exists:nexopos_orders,id',
            'assignments.*.order_product_id' => 'required|exists:nexopos_orders_products,id',
            'assignments.*.user_id' => 'required|exists:nexopos_users,id',
            'assignments.*.commission_id' => 'required|exists:nexopos_commissions,id',
        ]);

        $created = [];

        foreach ($request->assignments as $assignment) {
            $created[] = CommissionAssignment::updateOrCreate(
                [
                    'order_id' => $assignment['order_id'],
                    'order_product_id' => $assignment['order_product_id'],
                ],
                [
                    'user_id' => $assignment['user_id'],
                    'commission_id' => $assignment['commission_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => __('Commission assignments saved.'),
            'data' => $created,
        ]);
    }

    /**
     * Get commission assignments for an order
     */
    public function getOrderAssignments(Order $order): JsonResponse
    {
        $assignments = CommissionAssignment::where('order_id', $order->id)
            ->with(['user:id,username,email', 'commission:id,name,type,value'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $assignments,
        ]);
    }
}
