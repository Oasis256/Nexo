<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Modules\WhatsApp\Http\Controllers\Api\MessageController;
use Modules\WhatsApp\Http\Controllers\Api\StatisticsController;
use Modules\WhatsApp\Http\Controllers\Api\WebhookController;

/**
 * WhatsApp Module API Routes
 */

// Webhook endpoint (no auth - verified by signature)
Route::post('/whatsapp/webhook', [WebhookController::class, 'handle'])
    ->name('whatsapp.webhook');

Route::get('/whatsapp/webhook', [WebhookController::class, 'verify'])
    ->name('whatsapp.webhook.verify');

// Authenticated API routes
Route::middleware([Authenticate::class])
    ->prefix('whatsapp')
    ->group(function () {
        // Send messages
        Route::post('/send', [MessageController::class, 'send'])
            ->name('whatsapp.api.send');

        Route::post('/send/customer/{customer}', [MessageController::class, 'sendToCustomer'])
            ->name('whatsapp.api.send-customer');

        Route::post('/send/order/{order}', [MessageController::class, 'sendOrderNotification'])
            ->name('whatsapp.api.send-order');

        // Templates
        Route::get('/templates', [MessageController::class, 'getTemplates'])
            ->name('whatsapp.api.templates');

        Route::get('/templates/{template}/preview', [MessageController::class, 'previewTemplate'])
            ->name('whatsapp.api.template-preview');

        // Statistics
        Route::get('/statistics', [StatisticsController::class, 'index'])
            ->name('whatsapp.api.statistics');

        Route::get('/statistics/daily', [StatisticsController::class, 'daily'])
            ->name('whatsapp.api.statistics-daily');

        // Message logs
        Route::get('/logs', [MessageController::class, 'getLogs'])
            ->name('whatsapp.api.logs');

        Route::get('/logs/{log}', [MessageController::class, 'getLog'])
            ->name('whatsapp.api.log');

        Route::post('/logs/{log}/retry', [MessageController::class, 'retryMessage'])
            ->name('whatsapp.api.retry');

        // Configuration check
        Route::get('/status', [StatisticsController::class, 'status'])
            ->name('whatsapp.api.status');
    });
