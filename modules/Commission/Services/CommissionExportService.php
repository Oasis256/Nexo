<?php

namespace Modules\Commission\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Commission\Models\EarnedCommission;

class CommissionExportService
{
    protected CommissionReportService $reportService;

    public function __construct(CommissionReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Export commissions to CSV format
     */
    public function exportToCsv(Carbon $startDate, Carbon $endDate, ?int $userId = null): string
    {
        $data = $this->getExportData($startDate, $endDate, $userId);
        
        $csv = $this->generateCsvContent($data);
        
        return $csv;
    }

    /**
     * Get export data
     */
    protected function getExportData(Carbon $startDate, Carbon $endDate, ?int $userId = null): Collection
    {
        $query = EarnedCommission::query()
            ->with(['user:id,username,email', 'order:id,code', 'product:id,name,sku', 'commission:id,name,type'])
            ->betweenDates($startDate, $endDate)
            ->orderBy('user_id')
            ->orderBy('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * Generate CSV content from data
     */
    protected function generateCsvContent(Collection $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, [
            'ID',
            'Date',
            'User ID',
            'Username',
            'Email',
            'Order Code',
            'Product Name',
            'Product SKU',
            'Commission Name',
            'Commission Type',
            'Quantity',
            'Base Amount',
            'Commission Value',
        ]);

        // Write data rows
        foreach ($data as $item) {
            fputcsv($output, [
                $item->id,
                $item->created_at->format('Y-m-d H:i:s'),
                $item->user_id,
                $item->user?->username ?? 'Unknown',
                $item->user?->email ?? '',
                $item->order?->code ?? 'N/A',
                $item->product?->name ?? 'N/A',
                $item->product?->sku ?? 'N/A',
                $item->name,
                $item->commission_type,
                $item->quantity,
                $item->base_amount,
                $item->value,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export user summary to CSV
     */
    public function exportUserSummaryToCsv(Carbon $startDate, Carbon $endDate): string
    {
        $data = $this->reportService->getUserCommissionSummary($startDate, $endDate);

        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, [
            'User ID',
            'Username',
            'Email',
            'Total Orders',
            'Total Sales',
            'Total Commission',
        ]);

        // Write data rows
        foreach ($data as $item) {
            fputcsv($output, [
                $item['user_id'],
                $item['username'],
                $item['email'],
                $item['total_orders'],
                $item['total_sales'],
                $item['total_commission'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get payroll summary for export
     */
    public function getPayrollSummary(Carbon $startDate, Carbon $endDate): array
    {
        $summary = $this->reportService->getUserCommissionSummary($startDate, $endDate);
        $byType = $this->reportService->getCommissionByType($startDate, $endDate);

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'totals' => [
                'total_commission' => $summary->sum('total_commission'),
                'total_sales' => $summary->sum('total_sales'),
                'total_users' => $summary->count(),
                'total_orders' => $summary->sum('total_orders'),
            ],
            'by_type' => $byType,
            'by_user' => $summary->toArray(),
        ];
    }

    /**
     * Generate filename for export
     */
    public function generateFilename(string $type, Carbon $startDate, Carbon $endDate): string
    {
        $prefix = match ($type) {
            'detail' => 'commissions_detail',
            'summary' => 'commissions_summary',
            'payroll' => 'commissions_payroll',
            default => 'commissions',
        };

        return sprintf(
            '%s_%s_to_%s.csv',
            $prefix,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }
}
