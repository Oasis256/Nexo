<?php

namespace Modules\BookingVisitors\Adapters\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use Modules\BookingVisitors\Contracts\NotificationChannelInterface;

class WhatsAppBusinessApiAdapter implements NotificationChannelInterface
{
    public function send(string $channel, string $recipient, string $message, array $context = []): array
    {
        if ($channel !== 'whatsapp_business_api') {
            return [
                'status' => 'ignored',
                'provider' => 'whatsapp_business_api',
                'message' => 'Channel not handled by WhatsApp Business API adapter.',
            ];
        }

        $enabled = ns()->option->get('bookingvisitors_whatsapp_business_enabled', 'no') === 'yes';
        $phoneNumberId = (string) ns()->option->get('bookingvisitors_whatsapp_phone_number_id', '');
        $accessToken = (string) ns()->option->get('bookingvisitors_whatsapp_access_token', '');
        $apiVersion = (string) ns()->option->get('bookingvisitors_whatsapp_api_version', 'v20.0');

        if (! $enabled || $phoneNumberId === '' || $accessToken === '' || $recipient === '') {
            return [
                'status' => 'skipped',
                'provider' => 'whatsapp_business_api',
                'message' => 'WhatsApp Business API not configured.',
            ];
        }

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipient,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('[BookingVisitors] WhatsApp Business API send failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return [
                    'status' => 'error',
                    'provider' => 'whatsapp_business_api',
                    'http_status' => $response->status(),
                    'payload' => $response->json(),
                ];
            }

            return [
                'status' => 'sent',
                'provider' => 'whatsapp_business_api',
                'payload' => $response->json(),
            ];
        } catch (Throwable $exception) {
            Log::error('[BookingVisitors] WhatsApp Business API exception', [
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'error',
                'provider' => 'whatsapp_business_api',
                'message' => $exception->getMessage(),
            ];
        }
    }
}

