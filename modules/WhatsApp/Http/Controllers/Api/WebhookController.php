<?php

namespace Modules\WhatsApp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\WhatsApp\Enums\MessageStatus;
use Modules\WhatsApp\Events\WhatsAppMessageDeliveredEvent;
use Modules\WhatsApp\Events\WhatsAppWebhookReceivedEvent;
use Modules\WhatsApp\Models\MessageLog;

class WebhookController extends Controller
{
    /**
     * Verify webhook endpoint (GET request from Meta)
     */
    public function verify(Request $request): mixed
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = ns()->option->get('whatsapp_webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('[WhatsApp] Webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('[WhatsApp] Webhook verification failed', [
            'mode' => $mode,
            'token_match' => $token === $verifyToken,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming webhook events (POST from Meta)
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('[WhatsApp] Webhook received', ['payload' => $payload]);

        try {
            // Process webhook entries
            $entries = $payload['entry'] ?? [];

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $this->processChange($change);
                }
            }
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        // Always return 200 to acknowledge receipt
        return response()->json(['status' => 'ok']);
    }

    /**
     * Process a webhook change event
     */
    protected function processChange(array $change): void
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        if ($field !== 'messages') {
            return;
        }

        // Process message status updates
        $statuses = $value['statuses'] ?? [];

        foreach ($statuses as $status) {
            $this->processStatusUpdate($status);
        }

        // Dispatch event for any custom handling
        WhatsAppWebhookReceivedEvent::dispatch($value, $field);
    }

    /**
     * Process message status update
     */
    protected function processStatusUpdate(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusValue = $status['status'] ?? null;
        $timestamp = $status['timestamp'] ?? null;

        if (!$messageId || !$statusValue) {
            return;
        }

        // Find the message log by WhatsApp message ID
        $log = MessageLog::where('whatsapp_message_id', $messageId)->first();

        if (!$log) {
            Log::debug('[WhatsApp] Status update for unknown message', [
                'message_id' => $messageId,
                'status' => $statusValue,
            ]);
            return;
        }

        // Update status based on webhook data
        switch ($statusValue) {
            case 'sent':
                if ($log->status === MessageStatus::PENDING) {
                    $log->markAsSent($messageId);
                }
                break;

            case 'delivered':
                $log->markAsDelivered();
                WhatsAppMessageDeliveredEvent::dispatch($log);
                break;

            case 'read':
                $log->markAsRead();
                break;

            case 'failed':
                $errors = $status['errors'] ?? [];
                $errorMessage = $errors[0]['title'] ?? 'Message failed';
                $errorCode = $errors[0]['code'] ?? null;
                $log->markAsFailed($errorMessage, $errorCode);
                break;
        }

        Log::info('[WhatsApp] Message status updated', [
            'log_id' => $log->id,
            'message_id' => $messageId,
            'status' => $statusValue,
        ]);
    }
}
