<?php

namespace Modules\WhatsApp\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\DateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsApp\Enums\MessageStatus;
use Modules\WhatsApp\Enums\MessageType;
use Modules\WhatsApp\Enums\RecipientType;
use Modules\WhatsApp\Events\WhatsAppMessageFailedEvent;
use Modules\WhatsApp\Events\WhatsAppMessageSentEvent;
use Modules\WhatsApp\Models\MessageLog;
use Modules\WhatsApp\Models\MessageTemplate;

/**
 * Core WhatsApp Business API Service
 * Handles all communication with WhatsApp Cloud API
 */
class WhatsAppService
{
    protected string $apiVersion = 'v18.0';
    protected string $baseUrl = 'https://graph.facebook.com';

    protected ?string $accessToken = null;
    protected ?string $phoneNumberId = null;
    protected ?string $businessId = null;
    protected ?string $defaultCountryCode = null;

    protected DateService $dateService;

    public function __construct(DateService $dateService)
    {
        $this->dateService = $dateService;
        $this->loadConfiguration();
    }

    /**
     * Load configuration from options
     */
    protected function loadConfiguration(): void
    {
        $this->accessToken = ns()->option->get('whatsapp_access_token');
        $this->phoneNumberId = ns()->option->get('whatsapp_phone_number_id');
        $this->businessId = ns()->option->get('whatsapp_business_id');
        $this->defaultCountryCode = ns()->option->get('whatsapp_default_country_code', '+1');
    }

    /**
     * Check if WhatsApp integration is enabled and configured
     */
    public function isEnabled(): bool
    {
        return ns()->option->get('whatsapp_enabled', 'no') === 'yes'
            && !empty($this->accessToken)
            && !empty($this->phoneNumberId);
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->phoneNumberId);
    }

    /**
     * Get API endpoint URL
     */
    protected function getApiUrl(string $endpoint = ''): string
    {
        $base = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}";
        return $endpoint ? "{$base}/{$endpoint}" : $base;
    }

    /**
     * Format phone number with country code
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If already has + prefix, return as is (remove + for API)
        if (str_starts_with($phone, '+')) {
            return ltrim($phone, '+');
        }

        // If starts with 0, replace with country code
        if (str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        // Add default country code (without +)
        $countryCode = ltrim($this->defaultCountryCode, '+');
        
        return $countryCode . $phone;
    }

    /**
     * Send a text message
     */
    public function sendTextMessage(
        string $phone,
        string $message,
        ?string $recipientName = null,
        ?RecipientType $recipientType = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $authorId = null
    ): MessageLog {
        $formattedPhone = $this->formatPhoneNumber($phone);

        // Create log entry
        $log = MessageLog::create([
            'recipient_phone' => $formattedPhone,
            'recipient_name' => $recipientName,
            'recipient_type' => $recipientType ?? RecipientType::CUSTOMER,
            'recipient_id' => $recipientId,
            'message_type' => MessageType::TEXT,
            'content' => $message,
            'status' => MessageStatus::PENDING,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'author' => $authorId ?? auth()->id(),
        ]);

        if (!$this->isEnabled()) {
            $log->markAsFailed('WhatsApp integration is not enabled or configured');
            return $log;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->getApiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $formattedPhone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['messages'][0]['id'] ?? null;
                
                $log->markAsSent($messageId);
                
                WhatsAppMessageSentEvent::dispatch($log);
                
                Log::info('[WhatsApp] Message sent successfully', [
                    'log_id' => $log->id,
                    'message_id' => $messageId,
                    'phone' => $formattedPhone,
                ]);
            } else {
                $error = $response->json('error.message', 'Unknown error');
                $errorCode = $response->json('error.code');
                
                $log->markAsFailed($error, $errorCode);
                
                WhatsAppMessageFailedEvent::dispatch($log, $error);
                
                Log::error('[WhatsApp] Failed to send message', [
                    'log_id' => $log->id,
                    'phone' => $formattedPhone,
                    'error' => $error,
                    'code' => $errorCode,
                ]);
            }
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            
            WhatsAppMessageFailedEvent::dispatch($log, $e->getMessage());
            
            Log::error('[WhatsApp] Exception sending message', [
                'log_id' => $log->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send a template-based message
     */
    public function sendTemplateMessage(
        string $templateName,
        string $phone,
        array $data = [],
        ?string $recipientName = null,
        ?RecipientType $recipientType = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $authorId = null
    ): ?MessageLog {
        // Find the template
        $template = MessageTemplate::where('name', $templateName)
            ->active()
            ->first();

        if (!$template) {
            Log::warning('[WhatsApp] Template not found', ['name' => $templateName]);
            return null;
        }

        // Render the template with data
        $message = $template->render($data);

        $formattedPhone = $this->formatPhoneNumber($phone);

        // Create log entry
        $log = MessageLog::create([
            'template_id' => $template->id,
            'recipient_phone' => $formattedPhone,
            'recipient_name' => $recipientName,
            'recipient_type' => $recipientType ?? RecipientType::CUSTOMER,
            'recipient_id' => $recipientId,
            'message_type' => MessageType::TEMPLATE,
            'content' => $message,
            'status' => MessageStatus::PENDING,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'author' => $authorId ?? auth()->id(),
            'meta' => ['template_name' => $templateName, 'template_data' => $data],
        ]);

        if (!$this->isEnabled()) {
            $log->markAsFailed('WhatsApp integration is not enabled or configured');
            return $log;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->post($this->getApiUrl('messages'), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $formattedPhone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? null;
                
                $log->markAsSent($messageId);
                
                WhatsAppMessageSentEvent::dispatch($log);
                
                Log::info('[WhatsApp] Template message sent', [
                    'log_id' => $log->id,
                    'template' => $templateName,
                    'message_id' => $messageId,
                ]);
            } else {
                $error = $response->json('error.message', 'Unknown error');
                $errorCode = $response->json('error.code');
                
                $log->markAsFailed($error, $errorCode);
                
                WhatsAppMessageFailedEvent::dispatch($log, $error);
                
                Log::error('[WhatsApp] Failed to send template message', [
                    'log_id' => $log->id,
                    'template' => $templateName,
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            
            WhatsAppMessageFailedEvent::dispatch($log, $e->getMessage());
            
            Log::error('[WhatsApp] Exception sending template message', [
                'log_id' => $log->id,
                'exception' => $e->getMessage(),
            ]);
        }

        return $log;
    }

    /**
     * Send message to a customer
     */
    public function sendToCustomer(
        Customer $customer,
        string $templateName,
        array $additionalData = [],
        ?string $relatedType = null,
        ?int $relatedId = null
    ): ?MessageLog {
        if (empty($customer->phone)) {
            Log::warning('[WhatsApp] Customer has no phone number', ['customer_id' => $customer->id]);
            return null;
        }

        $data = array_merge([
            'customer_name' => $customer->name ?? $customer->first_name,
            'customer_first_name' => $customer->first_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'store_name' => ns()->option->get('ns_store_name', 'Store'),
            'store_phone' => ns()->option->get('ns_store_phone', ''),
            'store_address' => ns()->option->get('ns_store_address', ''),
            'current_date' => $this->dateService->getNowFormatted(),
            'current_time' => $this->dateService->getNowFormatted('H:i'),
        ], $additionalData);

        return $this->sendTemplateMessage(
            templateName: $templateName,
            phone: $customer->phone,
            data: $data,
            recipientName: $customer->name ?? $customer->first_name,
            recipientType: RecipientType::CUSTOMER,
            recipientId: $customer->id,
            relatedType: $relatedType,
            relatedId: $relatedId
        );
    }

    /**
     * Send message to a user (staff)
     */
    public function sendToUser(
        User $user,
        string $templateName,
        array $additionalData = [],
        ?string $relatedType = null,
        ?int $relatedId = null
    ): ?MessageLog {
        // Get user phone from meta or attribute
        $phone = $user->phone ?? $user->attribute?->phone ?? null;

        if (empty($phone)) {
            Log::warning('[WhatsApp] User has no phone number', ['user_id' => $user->id]);
            return null;
        }

        $data = array_merge([
            'store_name' => ns()->option->get('ns_store_name', 'Store'),
            'store_phone' => ns()->option->get('ns_store_phone', ''),
            'store_address' => ns()->option->get('ns_store_address', ''),
            'current_date' => $this->dateService->getNowFormatted(),
            'current_time' => $this->dateService->getNowFormatted('H:i'),
        ], $additionalData);

        return $this->sendTemplateMessage(
            templateName: $templateName,
            phone: $phone,
            data: $data,
            recipientName: $user->username,
            recipientType: RecipientType::USER,
            recipientId: $user->id,
            relatedType: $relatedType,
            relatedId: $relatedId
        );
    }

    /**
     * Build order data for templates
     */
    public function buildOrderData(Order $order): array
    {
        // Build products list
        $productsList = '';
        foreach ($order->products as $product) {
            $productsList .= sprintf(
                "• %s x%d - %s\n",
                $product->name,
                $product->quantity,
                ns()->currency->define($product->total_price)->format()
            );
        }

        return [
            'order_id' => $order->id,
            'order_code' => $order->code,
            'order_total' => ns()->currency->define($order->total)->format(),
            'order_subtotal' => ns()->currency->define($order->subtotal)->format(),
            'order_tax' => ns()->currency->define($order->tax_value)->format(),
            'order_discount' => ns()->currency->define($order->discount)->format(),
            'order_change' => ns()->currency->define($order->change)->format(),
            'order_tendered' => ns()->currency->define($order->tendered)->format(),
            'order_products' => trim($productsList),
            'order_date' => $this->dateService->getFormatted($order->created_at),
            'order_status' => $order->payment_status,
            'delivery_status' => $order->delivery_status,
        ];
    }

    /**
     * Send order notification to customer
     */
    public function notifyOrderCreated(Order $order): ?MessageLog
    {
        if (ns()->option->get('whatsapp_send_order_confirmation', 'yes') !== 'yes') {
            return null;
        }

        $customer = $order->customer;
        if (!$customer || empty($customer->phone)) {
            return null;
        }

        return $this->sendToCustomer(
            customer: $customer,
            templateName: 'order_confirmation',
            additionalData: $this->buildOrderData($order),
            relatedType: Order::class,
            relatedId: $order->id
        );
    }

    /**
     * Send payment received notification
     */
    public function notifyPaymentReceived(Order $order, $payment): ?MessageLog
    {
        if (ns()->option->get('whatsapp_send_payment_receipt', 'yes') !== 'yes') {
            return null;
        }

        $customer = $order->customer;
        if (!$customer || empty($customer->phone)) {
            return null;
        }

        $paymentData = [
            'payment_amount' => ns()->currency->define($payment->value)->format(),
            'payment_method' => $payment->identifier ?? 'Unknown',
            'payment_date' => $this->dateService->getFormatted($payment->created_at),
        ];

        return $this->sendToCustomer(
            customer: $customer,
            templateName: 'payment_received',
            additionalData: array_merge($this->buildOrderData($order), $paymentData),
            relatedType: Order::class,
            relatedId: $order->id
        );
    }

    /**
     * Send order refund notification
     */
    public function notifyOrderRefunded(Order $order, $refundAmount, $reason = ''): ?MessageLog
    {
        if (ns()->option->get('whatsapp_send_refund_notification', 'yes') !== 'yes') {
            return null;
        }

        $customer = $order->customer;
        if (!$customer || empty($customer->phone)) {
            return null;
        }

        $refundData = [
            'refund_amount' => ns()->currency->define($refundAmount)->format(),
            'refund_reason' => $reason ?: __m('Refund processed', 'WhatsApp'),
        ];

        return $this->sendToCustomer(
            customer: $customer,
            templateName: 'order_refunded',
            additionalData: array_merge($this->buildOrderData($order), $refundData),
            relatedType: Order::class,
            relatedId: $order->id
        );
    }

    /**
     * Send delivery status update
     */
    public function notifyDeliveryUpdate(Order $order): ?MessageLog
    {
        if (ns()->option->get('whatsapp_send_delivery_updates', 'yes') !== 'yes') {
            return null;
        }

        $customer = $order->customer;
        if (!$customer || empty($customer->phone)) {
            return null;
        }

        return $this->sendToCustomer(
            customer: $customer,
            templateName: 'delivery_update',
            additionalData: $this->buildOrderData($order),
            relatedType: Order::class,
            relatedId: $order->id
        );
    }

    /**
     * Send low stock alert to staff
     */
    public function notifyLowStock(array $products): void
    {
        if (ns()->option->get('whatsapp_send_low_stock_alerts', 'yes') !== 'yes') {
            return;
        }

        // Build products list
        $productsList = '';
        foreach ($products as $product) {
            $productsList .= sprintf(
                "• %s (SKU: %s) - Qty: %d\n",
                $product['name'] ?? $product->name ?? 'Unknown',
                $product['sku'] ?? $product->sku ?? 'N/A',
                $product['quantity'] ?? $product->quantity ?? 0
            );
        }

        $data = [
            'low_stock_products' => trim($productsList),
            'store_name' => ns()->option->get('ns_store_name', 'Store'),
            'current_date' => $this->dateService->getNowFormatted(),
            'current_time' => $this->dateService->getNowFormatted('H:i'),
        ];

        // Get staff roles that should receive alerts
        $roleNamespaces = ns()->option->get('whatsapp_staff_alert_roles', []);
        
        if (empty($roleNamespaces)) {
            $roleNamespaces = ['admin', 'nexopos.store.administrator'];
        }

        // Send to all users in those roles
        $users = User::whereHas('roles', function ($query) use ($roleNamespaces) {
            $query->whereIn('namespace', $roleNamespaces);
        })->get();

        foreach ($users as $user) {
            $this->sendToUser(
                user: $user,
                templateName: 'low_stock_alert',
                additionalData: $data
            );
        }
    }

    /**
     * Get message statistics
     */
    public function getStatistics(string $period = 'month'): array
    {
        $query = MessageLog::query();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $total = (clone $query)->count();
        $sent = (clone $query)->whereIn('status', [
            MessageStatus::SENT,
            MessageStatus::DELIVERED,
            MessageStatus::READ,
        ])->count();
        $delivered = (clone $query)->where('status', MessageStatus::DELIVERED)->count();
        $read = (clone $query)->where('status', MessageStatus::READ)->count();
        $failed = (clone $query)->where('status', MessageStatus::FAILED)->count();
        $pending = (clone $query)->where('status', MessageStatus::PENDING)->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'read' => $read,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 1) : 0,
            'delivery_rate' => $sent > 0 ? round((($delivered + $read) / $sent) * 100, 1) : 0,
            'read_rate' => $delivered > 0 ? round(($read / ($delivered + $read)) * 100, 1) : 0,
        ];
    }
}
