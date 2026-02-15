<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\BookingVisitors\Http\Controllers\DashboardController;

Route::prefix('dashboard/bookingvisitors')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.read'))
        ->name('bookingvisitors.dashboard');

    Route::get('/bookings', [DashboardController::class, 'bookings'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.read'))
        ->name('bookingvisitors.bookings');

    Route::get('/bookings/create', [DashboardController::class, 'createBooking'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.create'))
        ->name('bookingvisitors.bookings.create');

    Route::get('/bookings/edit/{booking}', [DashboardController::class, 'editBooking'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.update'))
        ->name('bookingvisitors.bookings.edit');

    Route::get('/checkins', [DashboardController::class, 'checkins'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.read'))
        ->name('bookingvisitors.checkins');

    Route::get('/guests', [DashboardController::class, 'guests'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.guest.access'))
        ->name('bookingvisitors.guests');

    Route::get('/guests/create', [DashboardController::class, 'createGuest'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.create'))
        ->name('bookingvisitors.guests.create');

    Route::get('/guests/edit/{guest}', [DashboardController::class, 'editGuest'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.update'))
        ->name('bookingvisitors.guests.edit');

    Route::get('/logs', [DashboardController::class, 'logs'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.bookingvisitors.reports.read'))
        ->name('bookingvisitors.logs');
});
