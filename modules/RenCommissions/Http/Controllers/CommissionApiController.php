<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Models\CommissionPayout;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\CommissionDashboardService;
use Modules\RenCommissions\Services\CommissionPayoutService;
use Modules\RenCommissions\Support\StoreContext;

class CommissionApiController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        private readonly CommissionDashboardService $dashboardService,
        private readonly CommissionPayoutService $payoutService
    ) {
        parent::__construct($dateService);
    }

    public function commissions(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request, 'all_time');
        $status = trim((string) $request->string('status'));
        $search = trim((string) $request->string('search'));

        $query = $this->dashboardService->applyPeriod(
            $this->storeScoped(OrderItemCommission::query()->with(['order', 'product', 'earner', 'type', 'payout'])),
            $period
        );

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->whereHas('order', fn (Builder $order) => $order->where('code', 'like', '%' . $search . '%'))
                    ->orWhereHas('product', fn (Builder $product) => $product->where('name', 'like', '%' . $search . '%'))
                    ->orWhereHas('earner', fn (Builder $earner) => $earner->where('username', 'like', '%' . $search . '%'));
            });
        }

        $rows = $query->latest('id')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rows->items(),
            'pagination' => [
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function staffEarnings(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request);

        $rows = $this->dashboardService->applyPeriod($this->storeScoped(OrderItemCommission::query()), $period)
            ->select(
                'earner_id',
                DB::raw('SUM(total_commission) as total_amount'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN total_commission ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_commission ELSE 0 END) as paid_amount"),
                DB::raw('COUNT(*) as rows_count')
            )
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->orderByDesc('total_amount')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rows->items(),
            'pagination' => [
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function paymentHistory(): JsonResponse
    {
        $rows = $this->storeScoped(CommissionPayout::query())->latest('id')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rows->items(),
            'pagination' => [
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function pendingPayouts(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request, 'all_time');

        $rows = $this->dashboardService->applyPeriod($this->storeScoped(OrderItemCommission::query()->with(['product', 'earner', 'order'])), $period)
            ->where('status', 'pending')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rows->items(),
            'pagination' => [
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function types(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->storeScoped(CommissionType::query())->orderByDesc('id')->get(),
        ]);
    }

    public function myCommissions(Request $request): JsonResponse
    {
        $period = $this->resolvePeriod($request);

        $rows = $this->dashboardService->applyPeriod(
            $this->storeScoped(OrderItemCommission::query()->with(['order', 'product', 'type'])->where('earner_id', auth()->id())),
            $period
        )
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $rows->items(),
            'pagination' => [
                'total' => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
            ],
        ]);
    }

    public function markPaid(OrderItemCommission $commission): JsonResponse
    {
        if (! StoreContext::matches($commission->store_id !== null ? (int) $commission->store_id : null)) {
            return response()->json(['status' => 'failed'], 403);
        }

        if ($commission->status === 'pending') {
            $this->payoutService->create(
                [$commission->id],
                auth()->id(),
                __m('Single payout from API mark paid.', 'RenCommissions')
            );
        }

        return response()->json(['status' => 'success']);
    }

    public function createPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commission_ids' => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payout = $this->payoutService->create(
            $validated['commission_ids'],
            auth()->id(),
            $validated['notes'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'data' => $payout,
        ]);
    }

    private function resolvePeriod(Request $request, string $default = 'this_month'): string
    {
        $period = (string) $request->string('period', $default);
        $allowed = ['today', 'this_week', 'this_month', 'last_month', 'last_30_days', 'this_year', 'all_time'];

        return in_array($period, $allowed, true) ? $period : $default;
    }

    private function storeScoped(Builder $query): Builder
    {
        return StoreContext::constrain($query);
    }
}
