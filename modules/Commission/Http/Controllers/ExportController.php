<?php

namespace Modules\Commission\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Commission\Services\CommissionExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles Commission Export functionality
 */
class ExportController extends Controller
{
    public function __construct(
        protected CommissionExportService $exportService
    ) {
    }

    /**
     * Export all earned commissions to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        ns()->restrict(['commission.export']);

        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->get('end_date', now()->endOfDay()->toDateTimeString()));
        $userId = $request->get('user_id');

        $filename = $this->exportService->generateFilename('detail', $startDate, $endDate);
        $csv = $this->exportService->exportToCsv($startDate, $endDate, $userId ? (int) $userId : null);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export user summary to CSV
     */
    public function exportUserSummary(Request $request): StreamedResponse
    {
        ns()->restrict(['commission.export']);

        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->get('end_date', now()->endOfDay()->toDateTimeString()));

        $filename = $this->exportService->generateFilename('summary', $startDate, $endDate);
        $csv = $this->exportService->exportUserSummaryToCsv($startDate, $endDate);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export payroll summary to CSV
     */
    public function exportPayroll(Request $request): StreamedResponse
    {
        ns()->restrict(['commission.export']);

        $startDate = Carbon::parse($request->get('start_date', now()->startOfMonth()->toDateString()));
        $endDate = Carbon::parse($request->get('end_date', now()->endOfDay()->toDateTimeString()));

        $filename = $this->exportService->generateFilename('payroll', $startDate, $endDate);
        $payrollData = $this->exportService->getPayrollSummary($startDate, $endDate);

        return response()->streamDownload(function () use ($payrollData, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'User ID',
                'Username',
                'Full Name',
                'Email',
                'Total Commissions',
                'Total Amount',
                'Period Start',
                'Period End',
            ]);

            // Data rows
            foreach ($payrollData['by_user'] as $row) {
                fputcsv($handle, [
                    $row['user_id'],
                    $row['username'],
                    $row['full_name'] ?? '',
                    $row['email'],
                    $row['total_orders'] ?? 0,
                    $row['total_commission'] ?? 0,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
