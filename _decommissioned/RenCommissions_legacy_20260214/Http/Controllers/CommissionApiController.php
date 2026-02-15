<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\PerItemCommissionService;
use Modules\RenCommissions\Services\PosCommissionCleanupService;

/**
 * Commission API Controller
 * 
 * Handles all commission-related API endpoints.
 */
class CommissionApiController extends Controller
{
    /**
     * Commission service
     */
    protected PerItemCommissionService $commissionService;

    /**
     * Constructor
     */
    public function __construct(PerItemCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Get eligible staff members who can earn commissions
     *
     * @return JsonResponse
     */
    public function getEligibleEarners(): JsonResponse
    {
        try {
            $earners = $this->commissionService->getEligibleEarners();

            return response()->json([
                'status' => 'success',
                'data' => $earners,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active commission types
     *
     * @return JsonResponse
     */
    public function getCommissionTypes(): JsonResponse
    {
        try {
            $types = $this->commissionService->getCommissionTypes();

            return response()->json([
                'status' => 'success',
                'data' => $types,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview commission calculation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function previewCommission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:nexopos_products,id',
            'commission_type' => 'required|string|in:percentage,fixed,on_the_house',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'numeric|min:0.01',
            'commission_value' => 'nullable|numeric|min:0',
        ]);

        try {
            $preview = $this->commissionService->previewCommission($validated);

            return response()->json([
                'status' => 'success',
                'data' => $preview,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign commission to a product in the POS session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function assignCommission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_index' => 'required|integer|min:0',
            'product_id' => 'required|integer|exists:nexopos_products,id',
            'staff_id' => 'required|integer|exists:nexopos_users,id',
            'commission_type' => 'required|string|in:percentage,fixed,on_the_house',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'numeric|min:0.01',
            'commission_value' => 'nullable|numeric|min:0',
        ]);

        try {
            $sessionCommission = $this->commissionService->assignPosCommission($validated);

            return response()->json([
                'status' => 'success',
                'message' => __('Commission assigned successfully'),
                'data' => $sessionCommission,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current session commissions
     *
     * @return JsonResponse
     */
    public function getSessionCommissions(): JsonResponse
    {
        try {
            $commissions = $this->commissionService->getSessionCommissions();
            $totals = $this->commissionService->getSessionTotals();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'commissions' => $commissions,
                    'totals' => $totals,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove commission from a specific product index
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeCommission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_index' => 'required|integer|min:0',
        ]);

        try {
            $removed = $this->commissionService->removeCommission($validated['product_index']);

            return response()->json([
                'status' => $removed ? 'success' : 'info',
                'message' => $removed 
                    ? __('Commission removed successfully') 
                    : __('No commission found for this product'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all session commissions
     *
     * @return JsonResponse
     */
    public function clearSession(): JsonResponse
    {
        try {
            $count = $this->commissionService->clearSession();

            return response()->json([
                'status' => 'success',
                'message' => __(':count commission(s) cleared', ['count' => $count]),
                'data' => ['deleted_count' => $count],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get commissions for an order
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function getOrderCommissions(int $orderId): JsonResponse
    {
        try {
            $commissions = $this->commissionService->getOrderCommissions($orderId);
            $summary = OrderItemCommission::getOrderSummary($orderId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'commissions' => $commissions,
                    'summary' => $summary,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get earner summary/report
     *
     * @param Request $request
     * @param int $earnerId
     * @return JsonResponse
     */
    public function getEarnerSummary(Request $request, int $earnerId): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $summary = $this->commissionService->getEarnerSummary(
                $earnerId,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'data' => $summary,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update commission status
     *
     * @param Request $request
     * @param int $commissionId
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $commissionId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,paid,cancelled,voided',
            'reason' => 'nullable|string|max:500',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        try {
            $commission = $this->commissionService->updateCommissionStatus(
                $commissionId,
                $validated['status'],
                [
                    'reason' => $validated['reason'] ?? null,
                    'payment_reference' => $validated['payment_reference'] ?? null,
                ]
            );

            return response()->json([
                'status' => 'success',
                'message' => __('Commission status updated'),
                'data' => $commission,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Void a commission
     *
     * @param Request $request
     * @param int $commissionId
     * @return JsonResponse
     */
    public function voidCommission(Request $request, int $commissionId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $commission = $this->commissionService->voidCommission(
                $commissionId,
                $validated['reason']
            );

            return response()->json([
                'status' => 'success',
                'message' => __('Commission voided successfully'),
                'data' => $commission,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run session cleanup (admin only)
     *
     * @return JsonResponse
     */
    public function runCleanup(): JsonResponse
    {
        try {
            $cleanupService = app()->make(PosCommissionCleanupService::class);
            $stats = $cleanupService->runCleanup();

            return response()->json([
                'status' => 'success',
                'message' => __('Cleanup completed'),
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get session stats (admin only)
     *
     * @return JsonResponse
     */
    public function getSessionStats(): JsonResponse
    {
        try {
            $cleanupService = app()->make(PosCommissionCleanupService::class);
            $stats = $cleanupService->getSessionStats();

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
