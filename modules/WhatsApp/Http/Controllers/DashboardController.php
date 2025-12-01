<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Modules\WhatsApp\Services\WhatsAppService;

class DashboardController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        protected WhatsAppService $whatsAppService
    ) {
        parent::__construct($dateService);
    }

    /**
     * WhatsApp Dashboard
     */
    public function index()
    {
        ns()->restrict(['whatsapp.dashboard']);

        return view('WhatsApp::dashboard', [
            'title' => __m('WhatsApp Dashboard', 'WhatsApp'),
            'description' => __m('Monitor WhatsApp messaging activity and statistics.', 'WhatsApp'),
            'isConfigured' => $this->whatsAppService->isConfigured(),
            'isEnabled' => $this->whatsAppService->isEnabled(),
        ]);
    }
}
