<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\RenCommissions\Http\Controllers\DashboardController;

/**
 * RenCommissions Web Routes
 */
Route::prefix('dashboard/rencommissions')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.commissions'))
        ->name('rencommissions.dashboard');

    Route::get('commissions', [DashboardController::class, 'commissions'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.commissions'))
        ->name('rencommissions.commissions');

    Route::get('types', [DashboardController::class, 'types'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.types'))
        ->name('rencommissions.types');

    Route::get('staff-earnings', [DashboardController::class, 'staffEarnings'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.commissions'))
        ->name('rencommissions.staff-earnings');

    Route::get('pending-payouts', [DashboardController::class, 'pendingPayouts'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.manage.payouts'))
        ->name('rencommissions.pending-payouts');

    Route::get('payment-history', [DashboardController::class, 'paymentHistory'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.commissions'))
        ->name('rencommissions.payment-history');

    Route::get('my-commissions', [DashboardController::class, 'myCommissions'])
        ->middleware(NsRestrictMiddleware::arguments('rencommissions.read.own'))
        ->name('rencommissions.my-commissions');
});
