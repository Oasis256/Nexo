<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\RenCommissions\Http\Controllers\CommissionApiController;
use Modules\RenCommissions\Http\Controllers\DashboardApiController;
use Modules\RenCommissions\Http\Controllers\PosApiController;

Route::prefix('rencommissions/dashboard')->group(function () {
    Route::get('/summary', [DashboardApiController::class, 'summary'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.dashboard'));
    Route::get('/recent', [DashboardApiController::class, 'recent'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.dashboard'));
    Route::get('/leaderboard', [DashboardApiController::class, 'leaderboard'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.dashboard'));
    Route::get('/trends', [DashboardApiController::class, 'trends'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.dashboard'));
});

Route::prefix('rencommissions')->group(function () {
    Route::get('/commissions', [CommissionApiController::class, 'commissions'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.commissions'));
    Route::post('/commissions/{commission}/mark-paid', [CommissionApiController::class, 'markPaid'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.update.commissions'));
    Route::get('/staff-earnings', [CommissionApiController::class, 'staffEarnings'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.reports'));
    Route::get('/payment-history', [CommissionApiController::class, 'paymentHistory'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.reports'));
    Route::get('/pending-payouts', [CommissionApiController::class, 'pendingPayouts'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'));
    Route::post('/pending-payouts/create', [CommissionApiController::class, 'createPayout'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.manage.payouts'));
    Route::get('/types', [CommissionApiController::class, 'types'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.types'));
    Route::get('/my-commissions', [CommissionApiController::class, 'myCommissions'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.read.own'));
    Route::get('/pos/types', [PosApiController::class, 'types'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.assign.pos'));
    Route::get('/pos/earners', [PosApiController::class, 'earners'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.assign.pos'));
    Route::post('/pos/session/assign', [PosApiController::class, 'assign'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.assign.pos'));
    Route::post('/pos/session/remove', [PosApiController::class, 'remove'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.assign.pos'));
    Route::post('/pos/session/clear', [PosApiController::class, 'clear'])
        ->middleware(NsRestrictMiddleware::arguments('nexopos.rencommissions.assign.pos'));
});
