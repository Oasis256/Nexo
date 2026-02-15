<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\BookingVisitors\Http\Controllers\Api\BookingIntakeController;
use Modules\BookingVisitors\Http\Controllers\Api\CheckInController;
use Modules\BookingVisitors\Http\Controllers\Api\GuestAccessController;
use Modules\BookingVisitors\Http\Controllers\Api\WhatsAppBusinessWebhookController;

Route::prefix('bookingvisitors')->group(function () {
    Route::get('/channels/whatsapp-business/webhook', [WhatsAppBusinessWebhookController::class, 'verify']);
    Route::post('/channels/whatsapp-business/webhook', [WhatsAppBusinessWebhookController::class, 'receive']);

    Route::post('/bookings/intake', [BookingIntakeController::class, 'store'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.api.expose'));

    Route::post('/qr/check-in', [CheckInController::class, 'store'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.checkin'));

    Route::post('/guest/validate', [GuestAccessController::class, 'store'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.guest.access'));
});
