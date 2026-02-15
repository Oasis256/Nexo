<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\RenCommissions\Http\Controllers\DashboardController;

Route::prefix('dashboard/rencommissions')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.dashboard'))
        ->name('rencommissions.dashboard');

    Route::get('/commissions', [DashboardController::class, 'commissions'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.commissions'))
        ->name('rencommissions.commissions');

    Route::post('/commissions/{commission}/mark-paid', [DashboardController::class, 'markPaid'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.update.commissions'))
        ->name('rencommissions.commissions.mark-paid');

    Route::post('/commissions/{commission}/void', [DashboardController::class, 'void'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.update.commissions'))
        ->name('rencommissions.commissions.void');

    Route::get('/staff-earnings', [DashboardController::class, 'staff'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.reports'))
        ->name('rencommissions.staff');

    Route::get('/pending-payouts', [DashboardController::class, 'pending'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'))
        ->name('rencommissions.pending');

    Route::get('/payout-interface', [DashboardController::class, 'payoutInterface'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'))
        ->name('rencommissions.payout-interface');

    Route::post('/pending-payouts/create', [DashboardController::class, 'createPayout'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'))
        ->name('rencommissions.pending.create-payout');
    Route::post('/pending-payouts/create-by-earner', [DashboardController::class, 'createPayoutByEarner'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'))
        ->name('rencommissions.pending.create-payout-by-earner');

    Route::get('/payment-history', [DashboardController::class, 'history'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.reports'))
        ->name('rencommissions.history');
    Route::get('/payment-history/{payoutId}/print', [DashboardController::class, 'printPayout'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.reports'))
        ->whereNumber('payoutId')
        ->name('rencommissions.history.print');

    Route::get('/types', [DashboardController::class, 'types'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.types'))
        ->name('rencommissions.types');

    Route::get('/types/create', [DashboardController::class, 'createType'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.create.types'))
        ->name('rencommissions.types.create');

    Route::get('/types/edit/{type}', [DashboardController::class, 'editType'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.update.types'))
        ->name('rencommissions.types.edit');

    Route::post('/types/{type}/toggle', [DashboardController::class, 'toggleType'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.update.types'))
        ->name('rencommissions.types.toggle');

    Route::get('/my-commissions', [DashboardController::class, 'myCommissions'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.own'))
        ->name('rencommissions.my-commissions');
});
