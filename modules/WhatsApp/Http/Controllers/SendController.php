<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Modules\WhatsApp\Models\MessageTemplate;
use Modules\WhatsApp\Services\WhatsAppService;

class SendController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        protected WhatsAppService $whatsAppService
    ) {
        parent::__construct($dateService);
    }

    /**
     * Send message form
     */
    public function index()
    {
        ns()->restrict(['whatsapp.send']);

        $templates = MessageTemplate::active()
            ->get()
            ->map(fn($t) => [
                'label' => $t->label,
                'value' => $t->name,
            ])
            ->toArray();

        return view('WhatsApp::send', [
            'title' => __m('Send WhatsApp Message', 'WhatsApp'),
            'description' => __m('Send a WhatsApp message to a customer or phone number.', 'WhatsApp'),
            'templates' => $templates,
            'isConfigured' => $this->whatsAppService->isConfigured(),
            'isEnabled' => $this->whatsAppService->isEnabled(),
        ]);
    }
}
