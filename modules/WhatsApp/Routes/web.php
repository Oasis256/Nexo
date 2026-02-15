<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ClearRequestCacheMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\WhatsApp\Http\Controllers\DashboardController;
use Modules\WhatsApp\Http\Controllers\LogController;
use Modules\WhatsApp\Http\Controllers\SendController;
use Modules\WhatsApp\Http\Controllers\TemplateController;

/**
 * WhatsApp Module Web Routes
 */
Route::middleware([
    Authenticate::class,
    ClearRequestCacheMiddleware::class,
])->prefix('dashboard/whatsapp')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->name('whatsapp.dashboard');

    // Message Templates CRUD
    Route::get('/templates', [TemplateController::class, 'index'])
        ->name('whatsapp.templates');

    Route::get('/templates/create', [TemplateController::class, 'create'])
        ->name('whatsapp.templates.create');

    Route::get('/templates/{template}/preview', [TemplateController::class, 'preview'])
        ->name('whatsapp.templates.preview');

    Route::get('/templates/edit/{template}', [TemplateController::class, 'edit'])
        ->name('whatsapp.templates.edit');

    // Message Logs
    Route::get('/logs', [LogController::class, 'index'])
        ->name('whatsapp.logs');

    Route::get('/logs/{log}', [LogController::class, 'view'])
        ->name('whatsapp.logs.view');

    // Send Message
    Route::get('/send', [SendController::class, 'index'])
        ->name('whatsapp.send');
});
