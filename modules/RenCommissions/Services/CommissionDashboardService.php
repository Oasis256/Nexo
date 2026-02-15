<?php

namespace Modules\RenCommissions\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Support\StoreContext;

class CommissionDashboardService
{
    public function applyStore(Builder $query): Builder
    {
        return StoreContext::constrain($query);
    }

    public function dateRange(string $period): ?array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_30_days' => [now()->subDays(30), now()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'all_time' => null,
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    public function applyPeriod(Builder $query, string $period): Builder
    {
        $range = $this->dateRange($period);
        if ($range === null) {
            return $query;
        }

        return $query->whereBetween('created_at', $range);
    }

    public function summary(string $period): array
    {
        $base = $this->applyPeriod($this->applyStore(OrderItemCommission::query()), $period);
        $totalAmount = (clone $base)->sum('total_commission');
        $totalCount = (clone $base)->count();
        $pendingAmount = (clone $base)->where('status', 'pending')->sum('total_commission');
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $paidAmount = (clone $base)->where('status', 'paid')->sum('total_commission');
        $paidCount = (clone $base)->where('status', 'paid')->count();
        $average = $totalCount > 0 ? ($totalAmount / $totalCount) : 0;

        return [
            'total' => ['amount' => (float) $totalAmount, 'count' => $totalCount, 'formatted' => ns()->currency->define($totalAmount)->format()],
            'pending' => ['amount' => (float) $pendingAmount, 'count' => $pendingCount, 'formatted' => ns()->currency->define($pendingAmount)->format()],
            'paid' => ['amount' => (float) $paidAmount, 'count' => $paidCount, 'formatted' => ns()->currency->define($paidAmount)->format()],
            'average' => ['amount' => (float) $average, 'formatted' => ns()->currency->define($average)->format()],
            'period' => $period,
        ];
    }

    public function trend(string $period): Collection
    {
        $query = $this->applyPeriod($this->applyStore(OrderItemCommission::query()), $period);

        return $query->select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
            DB::raw('SUM(total_commission) as total'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
