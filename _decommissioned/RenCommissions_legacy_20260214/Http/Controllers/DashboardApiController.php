<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Models\OrderItemCommission;

class DashboardApiController extends Controller
{
    public function getSummary(Request $request)
    {
        $period = $request->input('period', 'this_month');
        $dateRange = $this->getDateRange($period);
        $tableName = (new OrderItemCommission())->getTable();

        Log::info('[RenCommissions] Dashboard summary probe', [
            'url' => $request->fullUrl(),
            'period' => $period,
            'store_id' => ns()->store?->getCurrentStore()?->id,
            'table' => $tableName,
            'rows_total' => OrderItemCommission::count(),
            'rows_period' => OrderItemCommission::whereBetween('created_at', $dateRange)->count(),
        ]);

        $totalQuery = OrderItemCommission::whereBetween('created_at', $dateRange);
        $total = $totalQuery->sum('total_commission');
        $totalCount = $totalQuery->count();

        $pendingQuery = OrderItemCommission::where('status', 'pending')->whereBetween('created_at', $dateRange);
        $pending = $pendingQuery->sum('total_commission');
        $pendingCount = $pendingQuery->count();

        $paidQuery = OrderItemCommission::where('status', 'paid')->whereBetween('created_at', $dateRange);
        $paid = $paidQuery->sum('total_commission');
        $paidCount = $paidQuery->count();

        $avgPerOrder = OrderItemCommission::whereBetween('created_at', $dateRange)
            ->whereIn('status', ['pending', 'paid'])
            ->selectRaw('AVG(total_commission) as avg')
            ->value('avg') ?? 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => [
                    'amount' => (float) $total,
                    'count' => $totalCount,
                    'formatted' => ns()->currency->define($total)->format(),
                ],
                'pending' => [
                    'amount' => (float) $pending,
                    'count' => $pendingCount,
                    'formatted' => ns()->currency->define($pending)->format(),
                ],
                'paid' => [
                    'amount' => (float) $paid,
                    'count' => $paidCount,
                    'formatted' => ns()->currency->define($paid)->format(),
                ],
                'average' => [
                    'amount' => (float) $avgPerOrder,
                    'formatted' => ns()->currency->define($avgPerOrder)->format(),
                ],
                'period' => $period,
            ],
        ]);
    }

    public function getRecentCommissions(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $status = $request->input('status');
        $page = (int) $request->input('page', 1);

        $query = OrderItemCommission::with(['earner:id,username', 'product:id,name', 'order:id,code'])
            ->orderBy('created_at', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);
        $rows = collect($paginator->items())->map(fn($c) => [
            'id' => $c->id,
            'order_code' => $c->order?->code ?? 'N/A',
            'product_name' => $c->product?->name ?? 'Unknown',
            'earner_name' => $c->earner?->username ?? 'Unknown',
            'commission_type' => $c->commission_type,
            'total_commission' => (float) $c->total_commission,
            'formatted_amount' => ns()->currency->define($c->total_commission)->format(),
            'status' => $c->status,
            'created_at' => $c->created_at->toDateTimeString(),
            'created_at_human' => $c->created_at->diffForHumans(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getCommissions(Request $request)
    {
        $limit = (int) $request->input('limit', 20);
        $status = $request->input('status');
        $period = $request->input('period', 'this_month');
        $search = trim((string) $request->input('search', ''));
        $page = (int) $request->input('page', 1);
        $dateRange = $this->getDateRange($period);

        $query = OrderItemCommission::with(['earner:id,username', 'product:id,name', 'order:id,code'])
            ->orderBy('created_at', 'desc');

        if (!empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($period !== 'all_time') {
            $query->whereBetween('created_at', $dateRange);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->whereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('earner', fn($q) => $q->where('username', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn($q) => $q->where('code', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);
        $rows = collect($paginator->items())->map(fn($c) => [
            'id' => $c->id,
            'order_code' => $c->order?->code ?? 'N/A',
            'product_name' => $c->product?->name ?? 'Unknown',
            'earner_name' => $c->earner?->username ?? 'Unknown',
            'commission_type' => $c->commission_type,
            'total_commission' => (float) $c->total_commission,
            'formatted_amount' => ns()->currency->define($c->total_commission)->format(),
            'status' => $c->status,
            'created_at' => $c->created_at->toDateTimeString(),
            'created_at_human' => $c->created_at->diffForHumans(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getLeaderboard(Request $request)
    {
        $period = $request->input('period', 'this_month');
        $limit = (int) $request->input('limit', 10);
        $dateRange = $this->getDateRange($period);

        $rows = OrderItemCommission::select(
                'earner_id',
                DB::raw('SUM(total_commission) as total_earned'),
                DB::raw('COUNT(*) as commission_count')
            )
            ->whereBetween('created_at', $dateRange)
            ->whereIn('status', ['pending', 'paid'])
            ->groupBy('earner_id')
            ->orderByDesc('total_earned')
            ->limit($limit)
            ->with('earner:id,username')
            ->get()
            ->values()
            ->map(fn($item, $index) => [
                'rank' => $index + 1,
                'earner_id' => $item->earner_id,
                'earner_name' => $item->earner?->username ?? 'Unknown',
                'total_earned' => (float) $item->total_earned,
                'formatted_amount' => ns()->currency->define($item->total_earned)->format(),
                'commission_count' => $item->commission_count,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'period' => $period,
        ]);
    }

    public function getTrends(Request $request)
    {
        $period = $request->input('period', 'last_30_days');
        $groupBy = $request->input('group_by', 'day');
        $dateRange = $this->getDateRange($period);
        $dateFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $rows = OrderItemCommission::select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"),
                DB::raw('SUM(total_commission) as total'),
                DB::raw('COUNT(*) as count'),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_commission ELSE 0 END) as paid"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN total_commission ELSE 0 END) as pending")
            )
            ->whereBetween('created_at', $dateRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'total' => (float) $item->total,
                'count' => (int) $item->count,
                'paid' => (float) $item->paid,
                'pending' => (float) $item->pending,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'period' => $period,
            'group_by' => $groupBy,
        ]);
    }

    public function getStaffEarnings(Request $request)
    {
        $period = $request->input('period', 'this_month');
        $dateRange = $this->getDateRange($period);

        $query = OrderItemCommission::select(
                'earner_id',
                DB::raw('SUM(total_commission) as total_earned'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN total_commission ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_commission ELSE 0 END) as paid"),
                DB::raw('COUNT(*) as commission_count')
            )
            ->whereIn('status', ['pending', 'paid']);

        if ($period !== 'all_time') {
            $query->whereBetween('created_at', $dateRange);
        }

        $rows = $query->groupBy('earner_id')
            ->with('earner:id,username,email')
            ->orderByDesc(DB::raw('SUM(total_commission)'))
            ->get()
            ->map(fn($item) => [
                'earner_id' => $item->earner_id,
                'earner_name' => $item->earner?->username ?? 'Unknown',
                'earner_email' => $item->earner?->email ?? '',
                'total_earned' => (float) $item->total_earned,
                'pending' => (float) $item->pending,
                'paid' => (float) $item->paid,
                'formatted_total' => ns()->currency->define($item->total_earned)->format(),
                'formatted_pending' => ns()->currency->define($item->pending)->format(),
                'formatted_paid' => ns()->currency->define($item->paid)->format(),
                'commission_count' => (int) $item->commission_count,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
            'period' => $period,
        ]);
    }

    public function markPaid(int $id)
    {
        $commission = OrderItemCommission::findOrFail($id);
        if ($commission->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => __m('Only pending commissions can be marked as paid', 'RenCommissions'),
            ], 400);
        }

        $commission->status = 'paid';
        $commission->paid_at = now();
        $commission->paid_by = auth()->id();
        $commission->save();

        return response()->json([
            'status' => 'success',
            'message' => __m('Commission marked as paid', 'RenCommissions'),
        ]);
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $entries = $request->input('entries', []);

        if (empty($entries) || !is_array($entries)) {
            return response()->json([
                'status' => 'error',
                'message' => __m('No entries selected', 'RenCommissions'),
            ], 400);
        }

        if ($action === 'bulk_mark_paid') {
            $ids = array_map(fn($entry) => (int) ($entry['id'] ?? 0), $entries);
            $ids = array_values(array_filter($ids));

            $updated = OrderItemCommission::whereIn('id', $ids)
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                ]);

            return response()->json([
                'status' => 'success',
                'message' => sprintf(__m('%d commissions marked as paid', 'RenCommissions'), $updated),
            ]);
        }

        if ($action === 'bulk_mark_paid_by_earner') {
            $earnerIds = array_map(fn($entry) => (int) ($entry['earner_id'] ?? 0), $entries);
            $earnerIds = array_values(array_filter($earnerIds));

            $updated = OrderItemCommission::whereIn('earner_id', $earnerIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                ]);

            return response()->json([
                'status' => 'success',
                'message' => sprintf(__m('%d commissions marked as paid', 'RenCommissions'), $updated),
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => __m('Unknown action', 'RenCommissions'),
        ], 400);
    }

    public function exportCsv(Request $request)
    {
        $entries = $request->input('entries', []);
        $ids = array_map(fn($entry) => (int) ($entry['id'] ?? 0), is_array($entries) ? $entries : []);
        $ids = array_values(array_filter($ids));

        $commissions = OrderItemCommission::with(['earner:id,username', 'product:id,name', 'order:id,code'])
            ->when(!empty($ids), fn($query) => $query->whereIn('id', $ids))
            ->orderBy('created_at', 'desc')
            ->get();

        $csv = "ID,Order,Product,Earner,Type,Amount,Status,Date\n";
        foreach ($commissions as $c) {
            $csv .= implode(',', [
                $c->id,
                '"' . ($c->order?->code ?? 'N/A') . '"',
                '"' . str_replace('"', '""', $c->product?->name ?? 'Unknown') . '"',
                '"' . ($c->earner?->username ?? 'Unknown') . '"',
                $c->commission_type,
                $c->total_commission,
                $c->status,
                $c->created_at->toDateTimeString(),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="commissions-export.csv"',
        ]);
    }

    public function getMyCommissions(Request $request)
    {
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $paginator = OrderItemCommission::where('earner_id', auth()->id())
            ->with(['product:id,name', 'order:id,code'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function getMySummary(Request $request)
    {
        $userId = auth()->id();
        $period = $request->input('period', 'this_month');
        $dateRange = $this->getDateRange($period);

        $base = OrderItemCommission::where('earner_id', $userId);
        if ($period !== 'all_time') {
            $base->whereBetween('created_at', $dateRange);
        }

        $total = (clone $base)->sum('total_commission');
        $pending = (clone $base)->where('status', 'pending')->sum('total_commission');
        $paid = (clone $base)->where('status', 'paid')->sum('total_commission');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => [
                    'amount' => (float) $total,
                    'formatted' => ns()->currency->define($total)->format(),
                ],
                'pending' => [
                    'amount' => (float) $pending,
                    'formatted' => ns()->currency->define($pending)->format(),
                ],
                'paid' => [
                    'amount' => (float) $paid,
                    'formatted' => ns()->currency->define($paid)->format(),
                ],
                'period' => $period,
            ],
        ]);
    }

    public function getCommissionTypes()
    {
        $rows = CommissionType::orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($type) => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'calculation_method' => $type->calculation_method,
                'default_value' => (float) ($type->default_value ?? 0),
                'min_value' => $type->min_value !== null ? (float) $type->min_value : null,
                'max_value' => $type->max_value !== null ? (float) $type->max_value : null,
                'is_active' => (bool) $type->is_active,
                'priority' => (int) $type->priority,
                'is_system' => in_array($type->calculation_method, ['percentage', 'fixed', 'on_the_house'], true),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function createCommissionType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'calculation_method' => 'required|string|in:percentage,fixed,on_the_house',
            'default_value' => 'nullable|numeric|min:0',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        $type = CommissionType::create([
            ...$validated,
            'author' => auth()->id(),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'priority' => (int) ($validated['priority'] ?? 0),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __m('Commission type created', 'RenCommissions'),
            'data' => $type,
        ]);
    }

    public function updateCommissionType(Request $request, int $id)
    {
        $type = CommissionType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'calculation_method' => 'required|string|in:percentage,fixed,on_the_house',
            'default_value' => 'nullable|numeric|min:0',
            'min_value' => 'nullable|numeric|min:0',
            'max_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
        ]);

        if (in_array($type->calculation_method, ['percentage', 'fixed', 'on_the_house'], true)) {
            $validated['calculation_method'] = $type->calculation_method;
        }

        $type->fill($validated);
        $type->save();

        return response()->json([
            'status' => 'success',
            'message' => __m('Commission type updated', 'RenCommissions'),
            'data' => $type,
        ]);
    }

    public function deleteCommissionType(int $id)
    {
        $type = CommissionType::findOrFail($id);
        if (in_array($type->calculation_method, ['percentage', 'fixed', 'on_the_house'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => __m('System commission types cannot be deleted', 'RenCommissions'),
            ], 403);
        }

        $type->delete();

        return response()->json([
            'status' => 'success',
            'message' => __m('Commission type deleted', 'RenCommissions'),
        ]);
    }

    protected function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_30_days' => [now()->subDays(30), now()],
            'last_90_days' => [now()->subDays(90), now()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
