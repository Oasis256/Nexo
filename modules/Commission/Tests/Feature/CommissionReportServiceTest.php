<?php

namespace Modules\Commission\Tests\Feature;

use Modules\Commission\Models\Commission;
use Modules\Commission\Models\EarnedCommission;
use Modules\Commission\Services\CommissionReportService;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CommissionReportServiceTest extends TestCase
{
    use WithAuthentication;

    protected CommissionReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportService = app(CommissionReportService::class);
        $this->attemptAuthenticate();
    }

    /**
     * Test get top earners returns correct format
     */
    public function test_get_top_earners_format(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $topEarners = $this->reportService->getTopEarners($startDate, $endDate, 5);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $topEarners);

        if ($topEarners->isNotEmpty()) {
            $earner = $topEarners->first();
            
            $this->assertArrayHasKey('user_id', $earner);
            $this->assertArrayHasKey('username', $earner);
            $this->assertArrayHasKey('total_amount', $earner);
            $this->assertArrayHasKey('total_commission', $earner);
            $this->assertArrayHasKey('commission_count', $earner);
        }
    }

    /**
     * Test get recent commissions returns correct format
     */
    public function test_get_recent_commissions_format(): void
    {
        $recentCommissions = $this->reportService->getRecentCommissions(10);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recentCommissions);

        if ($recentCommissions->isNotEmpty()) {
            $commission = $recentCommissions->first();
            
            $this->assertArrayHasKey('id', $commission);
            $this->assertArrayHasKey('name', $commission);
            $this->assertArrayHasKey('amount', $commission);
            $this->assertArrayHasKey('username', $commission);
            $this->assertArrayHasKey('order_id', $commission);
            $this->assertArrayHasKey('product_name', $commission);
            $this->assertArrayHasKey('created_at', $commission);
        }
    }

    /**
     * Test get daily earnings returns correct format
     */
    public function test_get_daily_earnings_format(): void
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $dailyEarnings = $this->reportService->getDailyEarnings($startDate, $endDate);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $dailyEarnings);

        if ($dailyEarnings->isNotEmpty()) {
            $day = $dailyEarnings->first();
            
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('amount', $day);
            $this->assertArrayHasKey('count', $day);
        }
    }

    /**
     * Test get total earnings returns correct format
     */
    public function test_get_total_earnings_format(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $totals = $this->reportService->getTotalEarnings($startDate, $endDate);

        $this->assertIsArray($totals);
        $this->assertArrayHasKey('total', $totals);
        $this->assertArrayHasKey('formatted_total', $totals);
        $this->assertArrayHasKey('count', $totals);
        $this->assertIsFloat($totals['total']);
        $this->assertIsInt($totals['count']);
    }

    /**
     * Test get earnings comparison returns correct format
     */
    public function test_get_earnings_comparison_format(): void
    {
        $comparison = $this->reportService->getEarningsComparison('month');

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('current', $comparison);
        $this->assertArrayHasKey('previous', $comparison);
        $this->assertArrayHasKey('change_percent', $comparison);
        $this->assertArrayHasKey('trend', $comparison);
        
        $this->assertContains($comparison['trend'], ['up', 'down', 'stable']);
    }

    /**
     * Test get payroll report returns correct format
     */
    public function test_get_payroll_report_format(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $payroll = $this->reportService->getPayrollReport($startDate, $endDate);

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $payroll);

        if ($payroll->isNotEmpty()) {
            $entry = $payroll->first();
            
            $this->assertArrayHasKey('user_id', $entry);
            $this->assertArrayHasKey('username', $entry);
            $this->assertArrayHasKey('email', $entry);
            $this->assertArrayHasKey('total_commissions', $entry);
            $this->assertArrayHasKey('total_amount', $entry);
        }
    }

    /**
     * Test get commission by type returns all types
     */
    public function test_get_commission_by_type_format(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $byType = $this->reportService->getCommissionByType($startDate, $endDate);

        $this->assertIsArray($byType);
        $this->assertArrayHasKey('on_the_house', $byType);
        $this->assertArrayHasKey('fixed', $byType);
        $this->assertArrayHasKey('percentage', $byType);

        foreach (['on_the_house', 'fixed', 'percentage'] as $type) {
            $this->assertArrayHasKey('count', $byType[$type]);
            $this->assertArrayHasKey('total', $byType[$type]);
        }
    }

    /**
     * Test top earners limit is respected
     */
    public function test_top_earners_limit(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $topEarners = $this->reportService->getTopEarners($startDate, $endDate, 3);

        $this->assertLessThanOrEqual(3, $topEarners->count());
    }

    /**
     * Test recent commissions limit is respected
     */
    public function test_recent_commissions_limit(): void
    {
        $recentCommissions = $this->reportService->getRecentCommissions(5);

        $this->assertLessThanOrEqual(5, $recentCommissions->count());
    }

    /**
     * Test user-filtered summary
     */
    public function test_user_filtered_summary(): void
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Get any user with commissions
        $earned = EarnedCommission::first();
        
        if (!$earned) {
            $this->markTestSkipped('No earned commissions available for testing');
        }

        $summary = $this->reportService->getUserCommissionSummary(
            $startDate,
            $endDate,
            $earned->user_id
        );

        // Should only contain data for the specified user
        foreach ($summary as $item) {
            $this->assertEquals($earned->user_id, $item['user_id']);
        }
    }
}
