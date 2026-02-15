<?php

namespace Modules\Commission\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Models\User;
use App\Services\DateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Commission\Services\CommissionReportService;

/**
 * Handles Commission Dashboard and Reports
 */
class DashboardController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        protected CommissionReportService $reportService
    ) {
        parent::__construct($dateService);
    }

    /**
     * Commission dashboard
     */
    public function index(Request $request)
    {
        ns()->restrict(['commission.dashboard']);

        $dateRange = $this->getDateRange($request);

        $topEarners = $this->reportService->getTopEarners(
            startDate: Carbon::parse($dateRange['start']),
            endDate: Carbon::parse($dateRange['end']),
            limit: 10
        );

        $recentCommissions = $this->reportService->getRecentCommissions(10);

        $dailyEarnings = $this->reportService->getDailyEarnings(
            startDate: Carbon::parse($dateRange['start']),
            endDate: Carbon::parse($dateRange['end'])
        );

        return view('Commission::dashboard', [
            'title' => __m('Commission Dashboard', 'Commission'),
            'description' => __m('Overview of commission earnings and performance.', 'Commission'),
            'topEarners' => $topEarners,
            'recentCommissions' => $recentCommissions,
            'dailyEarnings' => $dailyEarnings,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Commission reports page
     */
    public function reports(Request $request)
    {
        ns()->restrict(['commission.reports']);

        $dateRange = $this->getDateRange($request);

        $payrollReport = $this->reportService->getPayrollReport(
            startDate: Carbon::parse($dateRange['start']),
            endDate: Carbon::parse($dateRange['end'])
        );

        return view('Commission::reports', [
            'title' => __m('Commission Reports', 'Commission'),
            'description' => __m('Generate and export commission reports.', 'Commission'),
            'payrollReport' => $payrollReport,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Individual user commission report
     */
    public function userReport(User $user, Request $request)
    {
        ns()->restrict(['commission.reports']);

        $dateRange = $this->getDateRange($request);

        $summary = $this->reportService->getUserCommissionSummary(
            startDate: Carbon::parse($dateRange['start']),
            endDate: Carbon::parse($dateRange['end']),
            userId: $user->id
        );

        $earnings = $user->earnedCommissions()
            ->with(['order', 'product', 'commission'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('Commission::reports.user', [
            'title' => sprintf(__m('%s Commission Report', 'Commission'), $user->username),
            'description' => __m('Individual commission report for this user.', 'Commission'),
            'user' => $user,
            'summary' => $summary,
            'earnings' => $earnings,
            'dateRange' => $dateRange,
        ]);
    }

    /**
     * Get date range from request or default to current month
     */
    protected function getDateRange(Request $request): array
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfDay()->toDateTimeString());

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }
}
