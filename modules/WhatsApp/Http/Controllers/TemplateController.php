<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Modules\WhatsApp\Crud\MessageTemplateCrud;
use Modules\WhatsApp\Models\MessageTemplate;

class TemplateController extends BaseDashboardController
{
    public function __construct(DateService $dateService)
    {
        parent::__construct($dateService);
    }

    /**
     * List message templates
     */
    public function index()
    {
        return MessageTemplateCrud::table();
    }

    /**
     * Create new template
     */
    public function create()
    {
        return MessageTemplateCrud::form();
    }

    /**
     * Edit existing template
     */
    public function edit(MessageTemplate $template)
    {
        return MessageTemplateCrud::form($template);
    }

    /**
     * Preview a template with sample data
     */
    public function preview(MessageTemplate $template)
    {
        ns()->restrict(['whatsapp.templates.read']);

        $sampleData = [
            'customer_name' => 'John Doe',
            'customer_first_name' => 'John',
            'customer_phone' => '+1234567890',
            'customer_email' => 'john@example.com',
            'order_id' => '123',
            'order_code' => 'ORD-2024-001',
            'order_total' => '$150.00',
            'order_subtotal' => '$140.00',
            'order_tax' => '$10.00',
            'order_discount' => '$0.00',
            'order_products' => "• Product A x2 - \$50.00\n• Product B x1 - \$90.00",
            'order_date' => date('Y-m-d H:i'),
            'order_status' => 'paid',
            'delivery_status' => 'pending',
            'payment_amount' => '$150.00',
            'payment_method' => 'Cash',
            'payment_date' => date('Y-m-d H:i'),
            'refund_amount' => '$50.00',
            'refund_reason' => 'Customer request',
            'store_name' => ns()->option->get('ns_store_name', 'My Store'),
            'store_phone' => ns()->option->get('ns_store_phone', '+1234567890'),
            'store_address' => ns()->option->get('ns_store_address', '123 Main St'),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i'),
            'low_stock_products' => "• Product X (SKU: X001) - Qty: 5\n• Product Y (SKU: Y002) - Qty: 3",
        ];

        $rendered = $template->render($sampleData);

        return view('WhatsApp::templates.preview', [
            'title' => __m('Template Preview', 'WhatsApp'),
            'description' => sprintf(__m('Preview of %s template with sample data.', 'WhatsApp'), $template->label),
            'template' => $template,
            'preview' => $rendered,
            'placeholders' => MessageTemplate::getAvailablePlaceholders(),
        ]);
    }
}
