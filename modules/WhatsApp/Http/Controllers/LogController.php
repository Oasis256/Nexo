<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Modules\WhatsApp\Crud\MessageLogCrud;
use Modules\WhatsApp\Models\MessageLog;

class LogController extends BaseDashboardController
{
    public function __construct(DateService $dateService)
    {
        parent::__construct($dateService);
    }

    /**
     * List message logs
     */
    public function index()
    {
        return MessageLogCrud::table();
    }

    /**
     * View message log details
     */
    public function view(MessageLog $log)
    {
        ns()->restrict(['whatsapp.logs.read']);

        $log->load('template');

        return view('WhatsApp::logs.view', [
            'title' => __m('Message Details', 'WhatsApp'),
            'description' => __m('View message details and status.', 'WhatsApp'),
            'log' => $log,
        ]);
    }
}
