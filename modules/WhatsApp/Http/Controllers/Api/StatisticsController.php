<?php

namespace Modules\WhatsApp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\WhatsApp\Enums\MessageStatus;
use Modules\WhatsApp\Models\MessageLog;
use Modules\WhatsApp\Services\WhatsAppService;

class StatisticsController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Get message statistics
     */
    public function index(Request $request): JsonResponse
    {
        ns()->restrict(['whatsapp.dashboard']);

        $period = $request->get('period', 'month');
        $stats = $this->whatsAppService->getStatistics($period);

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Get daily message counts
     */
    public function daily(Request $request): JsonResponse
    {
        ns()->restrict(['whatsapp.dashboard']);

        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $dailyStats = MessageLog::selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as sent', [
                MessageStatus::SENT->value,
                MessageStatus::DELIVERED->value,
                MessageStatus::READ->value,
            ])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [
                MessageStatus::FAILED->value,
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $dailyStats,
        ]);
    }

    /**
     * Get WhatsApp integration status
     */
    public function status(): JsonResponse
    {
        ns()->restrict(['whatsapp.dashboard']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => $this->whatsAppService->isEnabled(),
                'configured' => $this->whatsAppService->isConfigured(),
                'today_sent' => MessageLog::today()->sent()->count(),
                'today_failed' => MessageLog::today()->failed()->count(),
                'pending' => MessageLog::pending()->count(),
            ],
        ]);
    }
}
