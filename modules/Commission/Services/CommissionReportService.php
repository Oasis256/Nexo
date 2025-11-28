<?php

namespace Modules\Commission\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\EarnedCommission;

class CommissionReportService
{
    /**
     * Get commission summary by user for a date range
     */
    public function getUserCommissionSummary(
        Carbon $startDate,
        Carbon $endDate,
        ?int $userId = null
    ): Collection {
        $query = EarnedCommission::query()
            ->select([
                'user_id',
                DB::raw('COUNT(DISTINCT order_id) as total_orders'),
                DB::raw('SUM(value) as total_commission'),
                DB::raw('SUM(base_amount * quantity) as total_sales'),
            ])
            ->betweenDates($startDate, $endDate)
            ->groupBy('user_id')
            ->with('user:id,username,email');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->map(function ($item) {
            return [
                'user_id' => $item->user_id,
                'username' => $item->user?->username ?? 'Unknown',
                'email' => $item->user?->email ?? '',
                'total_orders' => $item->total_orders,
                'total_sales' => (float) $item->total_sales,
                'total_commission' => (float) $item->total_commission,
            ];
        });
    }

    /**
     * Get commission breakdown by type for a date range
     */
    public function getCommissionByType(
        Carbon $startDate,
        Carbon $endDate,
        ?int $userId = null
    ): array {
        $query = EarnedCommission::query()
            ->select([
                'commission_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(value) as total'),
            ])
            ->betweenDates($startDate, $endDate)
            ->groupBy('commission_type');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $results = $query->get();

        return [
            'on_the_house' => [
                'count' => $results->where('commission_type', 'on_the_house')->first()?->count ?? 0,
                'total' => (float) ($results->where('commission_type', 'on_the_house')->first()?->total ?? 0),
            ],
            'fixed' => [
                'count' => $results->where('commission_type', 'fixed')->first()?->count ?? 0,
                'total' => (float) ($results->where('commission_type', 'fixed')->first()?->total ?? 0),
            ],
            'percentage' => [
                'count' => $results->where('commission_type', 'percentage')->first()?->count ?? 0,
                'total' => (float) ($results->where('commission_type', 'percentage')->first()?->total ?? 0),
            ],
        ];
    }

    /**
     * Get top earners for a date range
     */
    public function getTopEarners(
        Carbon $startDate,
        Carbon $endDate,
        int $limit = 5
    ): Collection {
        return EarnedCommission::query()
            ->select([
                'user_id',
                DB::raw('SUM(value) as total_commission'),
                DB::raw('COUNT(*) as commission_count'),
            ])
            ->betweenDates($startDate, $endDate)
            ->groupBy('user_id')
            ->orderByDesc('total_commission')
            ->limit($limit)
            ->with('user:id,username,email')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'username' => $item->user?->username ?? 'Unknown',
                    'total_amount' => (float) $item->total_commission,
                    'total_commission' => (float) $item->total_commission,
                    'formatted_commission' => ns()->currency->define($item->total_commission)->format(),
                    'commission_count' => (int) $item->commission_count,
                ];
            });
    }

    /**
     * Get recent commissions
     */
    public function getRecentCommissions(int $limit = 10): Collection
    {
        return EarnedCommission::query()
            ->with(['user:id,username', 'order:id,code', 'product:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'amount' => (float) $item->value,
                    'value' => (float) $item->value,
                    'formatted_value' => ns()->currency->define($item->value)->format(),
                    'type' => $item->commission_type,
                    'username' => $item->user?->username ?? 'Unknown',
                    'user' => $item->user?->username ?? 'Unknown',
                    'order_id' => $item->order_id,
                    'order_code' => $item->order?->code ?? 'N/A',
                    'product_name' => $item->product?->name ?? 'N/A',
                    'product' => $item->product?->name ?? 'N/A',
                    'created_at' => $item->created_at->toISOString(),
                ];
            });
    }

    /**
     * Get total earnings for a period
     */
    public function getTotalEarnings(Carbon $startDate, Carbon $endDate, ?int $userId = null): array
    {
        $query = EarnedCommission::query()->betweenDates($startDate, $endDate);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total = $query->sum('value');
        $count = $query->count();

        return [
            'total' => (float) $total,
            'formatted_total' => ns()->currency->define($total)->format(),
            'count' => $count,
        ];
    }

    /**
     * Get earnings comparison (current vs previous period)
     */
    public function getEarningsComparison(string $period = 'month', ?int $userId = null): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'day':
                $currentStart = $now->copy()->startOfDay();
                $currentEnd = $now->copy()->endOfDay();
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $currentStart = $now->copy()->startOfWeek();
                $currentEnd = $now->copy()->endOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'month':
            default:
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        $current = $this->getTotalEarnings($currentStart, $currentEnd, $userId);
        $previous = $this->getTotalEarnings($previousStart, $previousEnd, $userId);

        $change = $previous['total'] > 0 
            ? (($current['total'] - $previous['total']) / $previous['total']) * 100 
            : ($current['total'] > 0 ? 100 : 0);

        return [
            'current' => $current,
            'previous' => $previous,
            'change_percent' => round($change, 2),
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get daily earnings for a date range (for charts)
     */
    public function getDailyEarnings(Carbon $startDate, Carbon $endDate, ?int $userId = null): Collection
    {
        $query = EarnedCommission::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(value) as total'),
                DB::raw('COUNT(*) as count'),
            ])
            ->betweenDates($startDate, $endDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'amount' => (float) $item->total,
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ];
        });
    }

    /**
     * Get detailed report for payroll export
     */
    public function getPayrollReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return EarnedCommission::query()
            ->select([
                'user_id',
                DB::raw('COUNT(*) as total_commissions'),
                DB::raw('SUM(value) as total_amount'),
            ])
            ->with(['user:id,username,email'])
            ->betweenDates($startDate, $endDate)
            ->groupBy('user_id')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($item) {
                $user = $item->user;
                return [
                    'user_id' => $item->user_id,
                    'username' => $user?->username ?? 'Unknown',
                    'email' => $user?->email ?? '',
                    'full_name' => trim(($user?->attribute?->first_name ?? '') . ' ' . ($user?->attribute?->last_name ?? '')),
                    'total_commissions' => (int) $item->total_commissions,
                    'total_amount' => (float) $item->total_amount,
                ];
            });
    }
}
