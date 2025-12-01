<?php

namespace Modules\WhatsApp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\WhatsApp\Enums\RecipientType;
use Modules\WhatsApp\Models\MessageLog;
use Modules\WhatsApp\Models\MessageTemplate;
use Modules\WhatsApp\Services\WhatsAppService;

class MessageController extends Controller
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Send a message to a phone number
     */
    public function send(Request $request): JsonResponse
    {
        ns()->restrict(['whatsapp.send']);

        $request->validate([
            'phone' => 'required|string',
            'message' => 'required_without:template|string|nullable',
            'template' => 'required_without:message|string|nullable',
            'recipient_name' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        if ($request->template) {
            $log = $this->whatsAppService->sendTemplateMessage(
                templateName: $request->template,
                phone: $request->phone,
                data: $request->data ?? [],
                recipientName: $request->recipient_name
            );
        } else {
            $log = $this->whatsAppService->sendTextMessage(
                phone: $request->phone,
                message: $request->message,
                recipientName: $request->recipient_name
            );
        }

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Failed to send message.', 'WhatsApp'),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __m('Message queued successfully.', 'WhatsApp'),
            'data' => [
                'log_id' => $log->id,
                'status' => $log->status->value,
            ],
        ]);
    }

    /**
     * Send message to a customer
     */
    public function sendToCustomer(Request $request, Customer $customer): JsonResponse
    {
        ns()->restrict(['whatsapp.send']);

        $request->validate([
            'template' => 'required|string',
            'data' => 'nullable|array',
        ]);

        if (empty($customer->phone)) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Customer has no phone number.', 'WhatsApp'),
            ], 400);
        }

        $log = $this->whatsAppService->sendToCustomer(
            customer: $customer,
            templateName: $request->template,
            additionalData: $request->data ?? []
        );

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Failed to send message.', 'WhatsApp'),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __m('Message sent to customer.', 'WhatsApp'),
            'data' => [
                'log_id' => $log->id,
                'status' => $log->status->value,
            ],
        ]);
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(Request $request, Order $order): JsonResponse
    {
        ns()->restrict(['whatsapp.send']);

        $request->validate([
            'template' => 'required|string',
        ]);

        $customer = $order->customer;

        if (!$customer || empty($customer->phone)) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Order has no customer or customer has no phone.', 'WhatsApp'),
            ], 400);
        }

        $log = $this->whatsAppService->sendToCustomer(
            customer: $customer,
            templateName: $request->template,
            additionalData: $this->whatsAppService->buildOrderData($order),
            relatedType: Order::class,
            relatedId: $order->id
        );

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Failed to send notification.', 'WhatsApp'),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __m('Order notification sent.', 'WhatsApp'),
            'data' => [
                'log_id' => $log->id,
                'status' => $log->status->value,
            ],
        ]);
    }

    /**
     * Get available templates
     */
    public function getTemplates(): JsonResponse
    {
        ns()->restrict(['whatsapp.templates.read']);

        $templates = MessageTemplate::active()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'label' => $t->label,
                'event' => $t->event,
                'target' => $t->target->value,
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $templates,
        ]);
    }

    /**
     * Preview a template with sample data
     */
    public function previewTemplate(Request $request, MessageTemplate $template): JsonResponse
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
            'order_products' => "• Product A x2 - $50.00\n• Product B x1 - $90.00",
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

        return response()->json([
            'status' => 'success',
            'data' => [
                'template' => $template->toArray(),
                'preview' => $rendered,
                'placeholders' => MessageTemplate::getAvailablePlaceholders(),
            ],
        ]);
    }

    /**
     * Get message logs
     */
    public function getLogs(Request $request): JsonResponse
    {
        ns()->restrict(['whatsapp.logs.read']);

        $query = MessageLog::with('template')
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('phone')) {
            $query->where('recipient_phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date,
            ]);
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * Get single message log
     */
    public function getLog(MessageLog $log): JsonResponse
    {
        ns()->restrict(['whatsapp.logs.read']);

        $log->load('template');

        return response()->json([
            'status' => 'success',
            'data' => $log,
        ]);
    }

    /**
     * Retry a failed message
     */
    public function retryMessage(MessageLog $log): JsonResponse
    {
        ns()->restrict(['whatsapp.send']);

        if ($log->status->value !== 'failed') {
            return response()->json([
                'status' => 'error',
                'message' => __m('Only failed messages can be retried.', 'WhatsApp'),
            ], 400);
        }

        // Create a new message with the same content
        $newLog = $this->whatsAppService->sendTextMessage(
            phone: $log->recipient_phone,
            message: $log->content,
            recipientName: $log->recipient_name,
            recipientType: $log->recipient_type,
            recipientId: $log->recipient_id,
            relatedType: $log->related_type,
            relatedId: $log->related_id
        );

        return response()->json([
            'status' => 'success',
            'message' => __m('Message retry queued.', 'WhatsApp'),
            'data' => [
                'original_log_id' => $log->id,
                'new_log_id' => $newLog->id,
                'status' => $newLog->status->value,
            ],
        ]);
    }
}
