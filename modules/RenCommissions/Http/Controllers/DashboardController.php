<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Throwable;
use Modules\RenCommissions\Crud\AllCommissionsCrud;
use Modules\RenCommissions\Crud\CommissionTypeCrud;
use Modules\RenCommissions\Crud\MyCommissionsCrud;
use Modules\RenCommissions\Crud\PaymentHistoryCrud;
use Modules\RenCommissions\Crud\PendingPayoutsCrud;
use Modules\RenCommissions\Crud\StaffEarningsCrud;
use Modules\RenCommissions\Models\CommissionPayout;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\CommissionDashboardService;
use Modules\RenCommissions\Services\CommissionPayoutService;
use Modules\RenCommissions\Support\StoreContext;

class DashboardController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        private readonly CommissionDashboardService $dashboardService,
        private readonly CommissionPayoutService $payoutService
    ) {
        parent::__construct($dateService);
    }

    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request);

        $recent = $this->periodQuery($this->storeScoped(OrderItemCommission::query()->with(['order', 'product', 'earner'])), $period)
            ->latest('id')
            ->limit(8)
            ->get();

        $topEarners = $this->periodQuery($this->storeScoped(OrderItemCommission::query()), $period)
            ->select('earner_id', DB::raw('SUM(total_commission) as total_amount'), DB::raw('COUNT(*) as total_count'))
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();

        $pendingPayouts = $this->storeScoped(OrderItemCommission::query())
            ->with(['product', 'earner'])
            ->where('status', 'pending')
            ->latest('id')
            ->limit(6)
            ->get();

        $paymentHistory = $this->storeScoped(CommissionPayout::query())
            ->latest('id')
            ->limit(8)
            ->get();

        return View::make('RenCommissions::dashboard.index', [
            'title' => __m('Commission Dashboard', 'RenCommissions'),
            'description' => __m('Monitor commissions, payouts and earnings.', 'RenCommissions'),
            'period' => $period,
            'periodOptions' => $this->periodOptions(),
            'summary' => $this->dashboardService->summary($period),
            'recent' => $recent,
            'topEarners' => $topEarners,
            'pendingPayouts' => $pendingPayouts,
            'paymentHistory' => $paymentHistory,
            'trend' => $this->dashboardService->trend($period),
        ]);
    }

    public function commissions(Request $request)
    {
        [$biweekly, $scope, $dailyDate, $earnerId] = $this->resolveRangeFilters($request);

        $earners = $this->storeScoped(OrderItemCommission::query())
            ->select('earner_id')
            ->whereNotNull('earner_id')
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->get()
            ->map(fn ($row) => $row->earner)
            ->filter()
            ->unique('id')
            ->sortBy('username')
            ->values();

        return View::make('RenCommissions::dashboard.commissions-list', [
            'title' => __m('All Commissions', 'RenCommissions'),
            'description' => __m('Browse and manage all commission records.', 'RenCommissions'),
            'src' => ns()->url('/api/crud/' . AllCommissionsCrud::IDENTIFIER),
            'queryParams' => [
                'biweekly' => $biweekly,
                'scope' => $scope,
                'daily_date' => $dailyDate,
                'earner_id' => $earnerId,
            ],
            'biweekly' => $biweekly,
            'scope' => $scope,
            'dailyDate' => $dailyDate,
            'earnerId' => $earnerId,
            'earners' => $earners,
        ]);
    }

    public function staff(Request $request)
    {
        [$biweekly, $scope, $dailyDate, $earnerId] = $this->resolveRangeFilters($request);

        $earners = $this->storeScoped(OrderItemCommission::query())
            ->select('earner_id')
            ->whereNotNull('earner_id')
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->get()
            ->map(fn ($row) => $row->earner)
            ->filter()
            ->unique('id')
            ->sortBy('username')
            ->values();

        return View::make('RenCommissions::dashboard.staff-list', [
            'title' => __m('Staff Earnings', 'RenCommissions'),
            'description' => __m('Compare commission totals by earner.', 'RenCommissions'),
            'src' => ns()->url('/api/crud/' . StaffEarningsCrud::IDENTIFIER),
            'queryParams' => [
                'biweekly' => $biweekly,
                'scope' => $scope,
                'daily_date' => $dailyDate,
                'earner_id' => $earnerId,
            ],
            'biweekly' => $biweekly,
            'scope' => $scope,
            'dailyDate' => $dailyDate,
            'earnerId' => $earnerId,
            'earners' => $earners,
        ]);
    }

    public function pending(Request $request)
    {
        return PendingPayoutsCrud::table();
    }

    public function payoutInterface(Request $request)
    {
        $biweekly = (string) $request->string('biweekly', $this->defaultBiweeklyWindow());
        if (! in_array($biweekly, ['first_half', 'second_half', 'all'], true)) {
            $biweekly = $this->defaultBiweeklyWindow();
        }

        $scope = (string) $request->string('scope', 'global');
        if (! in_array($scope, ['global', 'earner'], true)) {
            $scope = 'global';
        }

        $dailyDate = (string) $request->string('daily_date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dailyDate)) {
            $dailyDate = now()->toDateString();
        }

        $earnerId = (int) $request->input('earner_id', 0);
        if ($earnerId < 0) {
            $earnerId = 0;
        }

        $monthAnchor = Carbon::parse($dailyDate);
        $filterBaseQuery = $this->storeScoped(OrderItemCommission::query())
            ->where('status', 'pending');

        $this->applyBiweeklyWindow($filterBaseQuery, $biweekly, $monthAnchor);
        if ($scope === 'earner' && $earnerId > 0) {
            $filterBaseQuery->where('earner_id', $earnerId);
        }

        $pendingRows = (clone $filterBaseQuery)
            ->with(['order', 'product', 'earner'])
            ->latest('id')
            ->paginate(20)
            ->appends($request->query());

        $recentPayouts = $this->storeScoped(CommissionPayout::query())
            ->latest('id')
            ->limit(10)
            ->get();

        $pendingAmount = (clone $filterBaseQuery)->sum('total_commission');

        $earners = $this->storeScoped(OrderItemCommission::query())
            ->select('earner_id')
            ->whereNotNull('earner_id')
            ->where('status', 'pending')
            ->with('earner:id,username')
            ->groupBy('earner_id')
            ->get()
            ->map(fn ($row) => $row->earner)
            ->filter()
            ->unique('id')
            ->sortBy('username')
            ->values();

        $dailyBase = $this->storeScoped(OrderItemCommission::query())
            ->where('status', 'pending')
            ->whereDate('created_at', $dailyDate);
        $this->applyBiweeklyWindow($dailyBase, $biweekly, $monthAnchor);

        $dailyGlobal = [
            'count' => (clone $dailyBase)->count(),
            'amount' => (float) (clone $dailyBase)->sum('total_commission'),
        ];

        $dailyByEarner = collect();
        if ($scope === 'earner') {
            $dailyByEarnerQuery = (clone $dailyBase)
                ->select('earner_id', DB::raw('SUM(total_commission) as total_amount'), DB::raw('COUNT(*) as total_count'))
                ->with('earner:id,username')
                ->groupBy('earner_id')
                ->orderByDesc('total_amount');

            if ($earnerId > 0) {
                $dailyByEarnerQuery->where('earner_id', $earnerId);
            }

            $dailyByEarner = $dailyByEarnerQuery->get();
        }

        return View::make('RenCommissions::dashboard.payout-interface', [
            'title' => __m('Payout Interface', 'RenCommissions'),
            'description' => __m('Create payout batches from pending commissions.', 'RenCommissions'),
            'pendingRows' => $pendingRows,
            'recentPayouts' => $recentPayouts,
            'pendingAmount' => $pendingAmount,
            'biweekly' => $biweekly,
            'scope' => $scope,
            'dailyDate' => $dailyDate,
            'earnerId' => $earnerId,
            'earners' => $earners,
            'dailyGlobal' => $dailyGlobal,
            'dailyByEarner' => $dailyByEarner,
        ]);
    }

    public function history(Request $request)
    {
        return PaymentHistoryCrud::table();
    }

    public function printPayout(int $payoutId)
    {
        $payout = CommissionPayout::query()->findOrFail($payoutId);

        if (! StoreContext::matches($payout->store_id !== null ? (int) $payout->store_id : null)) {
            abort(404);
        }

        $payableDate = $payout->period_end ? $payout->period_end->toDateString() : now()->toDateString();

        $resolvedTable = (new OrderItemCommission())->getTable();
        $baseTable = 'rencommissions_order_item_commissions';
        $storeTable = StoreContext::id() ? 'store_' . StoreContext::id() . '_' . $baseTable : null;

        $candidateTables = collect([$resolvedTable, $baseTable, $storeTable])
            ->filter()
            ->unique()
            ->values();

        $rows = collect();
        $resolvedFromTable = null;
        foreach ($candidateTables as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $rows = DB::table($table . ' as c')
                ->leftJoin('nexopos_users as earner', 'c.earner_id', '=', 'earner.id')
                ->where('c.payout_id', $payout->id)
                ->groupBy('c.earner_id', 'earner.first_name', 'earner.last_name', 'earner.username', 'earner.email')
                ->orderBy('earner.first_name')
                ->orderBy('earner.last_name')
                ->orderBy('earner.username')
                ->get([
                    DB::raw('c.earner_id as earner_id'),
                    DB::raw('COALESCE(NULLIF(TRIM(CONCAT(COALESCE(earner.first_name, ""), " ", COALESCE(earner.last_name, ""))), ""), earner.username, earner.email, "' . __m('Unknown', 'RenCommissions') . '") as earner_name'),
                    DB::raw('COALESCE(earner.username, earner.email, "N/A") as earner_identifier'),
                    DB::raw('SUM(c.total_commission) as total_amount'),
                    DB::raw('COUNT(*) as entries_count'),
                ]);

            if ($rows->isNotEmpty()) {
                $resolvedFromTable = $table;
                break;
            }
        }

        Log::info('[RenCommissions] Payout print probe', [
            'payout_id' => $payout->id,
            'reference' => $payout->reference,
            'store_id' => $payout->store_id,
            'entries_count' => $payout->entries_count,
            'candidate_tables' => $candidateTables->all(),
            'resolved_from_table' => $resolvedFromTable,
            'rows_count' => $rows->count(),
        ]);

        return View::make('RenCommissions::dashboard.payout-print', [
            'title' => __m('Payout Pay Document', 'RenCommissions'),
            'payout' => $payout,
            'rows' => $rows,
            'payableDate' => $payableDate,
            'generatedAt' => now(),
        ]);
    }

    public function types(Request $request)
    {
        return CommissionTypeCrud::table();
    }

    public function createType()
    {
        return CommissionTypeCrud::form();
    }

    public function editType(CommissionType $type)
    {
        if (! StoreContext::matches($type->store_id !== null ? (int) $type->store_id : null)) {
            abort(404);
        }

        return CommissionTypeCrud::form($type);
    }

    public function myCommissions(Request $request)
    {
        return MyCommissionsCrud::table();
    }

    public function markPaid(OrderItemCommission $commission): RedirectResponse
    {
        if (! StoreContext::matches($commission->store_id !== null ? (int) $commission->store_id : null)) {
            return redirect()->back();
        }

        if ($commission->status === 'pending') {
            $this->payoutService->create(
                [$commission->id],
                auth()->id(),
                __m('Single payout from commission list.', 'RenCommissions')
            );
        }

        return redirect()->back();
    }

    public function void(OrderItemCommission $commission, Request $request): RedirectResponse
    {
        if (! StoreContext::matches($commission->store_id !== null ? (int) $commission->store_id : null)) {
            return redirect()->back();
        }

        if ($commission->status === 'pending') {
            $commission->status = 'voided';
            $commission->voided_at = now();
            $commission->voided_by = auth()->id();
            $commission->void_reason = $request->string('reason')->toString() ?: __m('Voided from dashboard', 'RenCommissions');
            $commission->save();
        }

        return redirect()->back();
    }

    public function createPayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_ids' => ['nullable', 'array'],
            'commission_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ids = collect($validated['commission_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            $ids = $this->storeScoped(OrderItemCommission::query())->where('status', 'pending')->pluck('id');
        }

        try {
            $this->payoutService->create($ids->all(), auth()->id(), $validated['notes'] ?? null);
        } catch (Throwable $exception) {
            return redirect()->back()->withErrors([
                'payout' => $exception->getMessage(),
            ]);
        }

        return redirect()->back();
    }

    public function createPayoutByEarner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_ids' => ['nullable', 'array'],
            'commission_ids.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ids = collect($validated['commission_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();

        $baseQuery = $this->storeScoped(OrderItemCommission::query())->where('status', 'pending');
        if ($ids->isNotEmpty()) {
            $baseQuery->whereIn('id', $ids->all());
        }

        $rows = $baseQuery->get(['id', 'earner_id']);
        if ($rows->isEmpty()) {
            return redirect()->back();
        }

        $groups = $rows
            ->filter(fn ($row) => ! empty($row->earner_id))
            ->groupBy('earner_id');

        foreach ($groups as $earnerId => $entries) {
            try {
                $this->payoutService->create(
                    $entries->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    auth()->id(),
                    trim(($validated['notes'] ?? '') . ' [' . __m('Earner', 'RenCommissions') . ': ' . $earnerId . ']')
                );
            } catch (Throwable $exception) {
                return redirect()->back()->withErrors([
                    'payout' => $exception->getMessage(),
                ]);
            }
        }

        return redirect()->back();
    }

    public function storeType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'calculation_method' => ['required', 'in:fixed,percentage,on_the_house'],
            'default_value' => ['required', 'numeric', 'min:0'],
            'min_value' => ['nullable', 'numeric', 'min:0'],
            'max_value' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ]);

        CommissionType::create([
            'store_id' => StoreContext::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'calculation_method' => $validated['calculation_method'],
            'default_value' => $validated['default_value'],
            'min_value' => $validated['min_value'] ?? null,
            'max_value' => $validated['max_value'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'is_active' => true,
            'author' => auth()->id(),
        ]);

        return redirect()->back();
    }

    public function toggleType(CommissionType $type): RedirectResponse
    {
        $type->is_active = ! $type->is_active;
        $type->save();

        return redirect()->back();
    }

    private function resolvePeriod(Request $request, string $default = 'this_month'): string
    {
        $period = (string) $request->string('period', $default);
        $allowed = array_keys($this->periodOptions());

        return in_array($period, $allowed, true) ? $period : $default;
    }

    private function periodQuery(Builder $query, string $period): Builder
    {
        return $this->dashboardService->applyPeriod($query, $period);
    }

    private function storeScoped(Builder $query): Builder
    {
        return StoreContext::constrain($query);
    }

    private function periodOptions(): array
    {
        return [
            'today' => __m('Today', 'RenCommissions'),
            'this_week' => __m('This Week', 'RenCommissions'),
            'this_month' => __m('This Month', 'RenCommissions'),
            'last_month' => __m('Last Month', 'RenCommissions'),
            'last_30_days' => __m('Last 30 Days', 'RenCommissions'),
            'this_year' => __m('This Year', 'RenCommissions'),
            'all_time' => __m('All Time', 'RenCommissions'),
        ];
    }

    private function defaultBiweeklyWindow(): string
    {
        return now()->day <= 14 ? 'first_half' : 'second_half';
    }

    private function resolveRangeFilters(Request $request): array
    {
        $biweekly = (string) $request->string('biweekly', $this->defaultBiweeklyWindow());
        if (! in_array($biweekly, ['first_half', 'second_half', 'all'], true)) {
            $biweekly = $this->defaultBiweeklyWindow();
        }

        $scope = (string) $request->string('scope', 'global');
        if (! in_array($scope, ['global', 'earner'], true)) {
            $scope = 'global';
        }

        $dailyDate = (string) $request->string('daily_date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dailyDate)) {
            $dailyDate = now()->toDateString();
        }

        $earnerId = (int) $request->input('earner_id', 0);
        if ($earnerId < 0) {
            $earnerId = 0;
        }

        return [$biweekly, $scope, $dailyDate, $earnerId];
    }

    private function applyBiweeklyWindow(Builder $query, string $biweekly, Carbon $anchor): Builder
    {
        if ($biweekly === 'all') {
            return $query;
        }

        $monthStart = $anchor->copy()->startOfMonth()->startOfDay();
        $firstHalfEnd = $anchor->copy()->startOfMonth()->day(14)->endOfDay();
        $secondHalfStart = $anchor->copy()->startOfMonth()->day(15)->startOfDay();
        $monthEnd = $anchor->copy()->endOfMonth()->endOfDay();

        if ($biweekly === 'first_half') {
            return $query->whereBetween('created_at', [$monthStart, $firstHalfEnd]);
        }

        return $query->whereBetween('created_at', [$secondHalfStart, $monthEnd]);
    }
}
