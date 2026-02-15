<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\CommissionDashboardService;
use Modules\RenCommissions\Support\StoreContext;

class DashboardApiController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        private readonly CommissionDashboardService $dashboardService
    ) {
        parent::__construct($dateService);
    }

    public function summary(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request);

        return response()->json([
            'status' => 'success',
            'data' => $this->dashboardService->summary($period),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 8), 1), 50);

        $rows = StoreContext::constrain(OrderItemCommission::query())
            ->with(['order', 'product', 'earner'])
            ->latest('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request);
        $limit = min(max((int) $request->input('limit', 6), 1), 50);

        $rows = $this->dashboardService->applyPeriod(
            StoreContext::constrain(OrderItemCommission::query()),
            $period
        )
            ->select('earner_id', DB::raw('SUM(total_commission) as total_amount'), DB::raw('COUNT(*) as total_count'))
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'period' => $period,
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request);

        return response()->json([
            'status' => 'success',
            'data' => $this->dashboardService->trend($period),
            'period' => $period,
            'group_by' => 'day',
        ]);
    }

    private function resolvePeriod(Request $request): string
    {
        $period = (string) $request->string('period', 'this_month');
        $allowed = ['today', 'this_week', 'this_month', 'last_month', 'last_30_days', 'this_year', 'all_time'];

        return in_array($period, $allowed, true) ? $period : 'this_month';
    }
}
