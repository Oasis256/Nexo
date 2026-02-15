<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\RenCommissions\Http\Controllers\CommissionApiController;
use Modules\RenCommissions\Http\Controllers\DashboardApiController;

/**
 * RenCommissions API Routes
 * 
 * All routes are prefixed with /api/rencommissions/
 */

Route::prefix('rencommissions')->group(function () {

    // POS-related endpoints (require POS access)
    Route::middleware(NsRestrictMiddleware::arguments('nexopos.create.orders'))->group(function () {
        
        // Get eligible staff for commission assignment
        Route::get('earners', [CommissionApiController::class, 'getEligibleEarners'])
            ->name('rencommissions.api.earners');

        // Get active commission types
        Route::get('types', [CommissionApiController::class, 'getCommissionTypes'])
            ->name('rencommissions.api.types');

        // Preview commission calculation
        Route::post('preview', [CommissionApiController::class, 'previewCommission'])
            ->name('rencommissions.api.preview');

        // Session management
        Route::post('session/assign', [CommissionApiController::class, 'assignCommission'])
            ->name('rencommissions.api.session.assign');

        Route::get('session', [CommissionApiController::class, 'getSessionCommissions'])
            ->name('rencommissions.api.session');

        Route::delete('session/remove', [CommissionApiController::class, 'removeCommission'])
            ->name('rencommissions.api.session.remove');

        Route::delete('session/clear', [CommissionApiController::class, 'clearSession'])
            ->name('rencommissions.api.session.clear');
    });

    // Order commission endpoints (require orders read access)
    Route::middleware(NsRestrictMiddleware::arguments('nexopos.read.orders'))->group(function () {
        
        Route::get('orders/{orderId}', [CommissionApiController::class, 'getOrderCommissions'])
            ->name('rencommissions.api.order');
    });

    // Dashboard data
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.read.commissions'))->group(function () {
        Route::get('dashboard/summary', [DashboardApiController::class, 'getSummary'])
            ->name('rencommissions.api.dashboard.summary');
        Route::get('dashboard/recent', [DashboardApiController::class, 'getRecentCommissions'])
            ->name('rencommissions.api.dashboard.recent');
        Route::get('dashboard/commissions', [DashboardApiController::class, 'getCommissions'])
            ->name('rencommissions.api.dashboard.commissions');
        Route::get('dashboard/leaderboard', [DashboardApiController::class, 'getLeaderboard'])
            ->name('rencommissions.api.dashboard.leaderboard');
        Route::get('dashboard/trends', [DashboardApiController::class, 'getTrends'])
            ->name('rencommissions.api.dashboard.trends');
        Route::get('dashboard/staff-earnings', [DashboardApiController::class, 'getStaffEarnings'])
            ->name('rencommissions.api.dashboard.staff-earnings');
    });

    // Commission types management
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.read.types'))->group(function () {
        Route::get('dashboard/types', [DashboardApiController::class, 'getCommissionTypes'])
            ->name('rencommissions.api.dashboard.types');
    });
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.create.types'))->group(function () {
        Route::post('dashboard/types', [DashboardApiController::class, 'createCommissionType'])
            ->name('rencommissions.api.dashboard.types.create');
    });
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.update.types'))->group(function () {
        Route::put('dashboard/types/{id}', [DashboardApiController::class, 'updateCommissionType'])
            ->name('rencommissions.api.dashboard.types.update');
    });
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.delete.types'))->group(function () {
        Route::delete('dashboard/types/{id}', [DashboardApiController::class, 'deleteCommissionType'])
            ->name('rencommissions.api.dashboard.types.delete');
    });

    // Commission actions
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.update.commissions'))->group(function () {
        
        Route::put('commissions/{id}/status', [CommissionApiController::class, 'updateStatus'])
            ->name('rencommissions.api.commission.status');

        Route::post('commissions/{id}/void', [CommissionApiController::class, 'voidCommission'])
            ->name('rencommissions.api.commission.void');

        Route::post('commissions/{id}/mark-paid', [DashboardApiController::class, 'markPaid'])
            ->name('rencommissions.api.commission.mark-paid');
        Route::post('commissions/bulk-action', [DashboardApiController::class, 'bulkAction'])
            ->name('rencommissions.api.commission.bulk');
        Route::post('commissions/export', [DashboardApiController::class, 'exportCsv'])
            ->name('rencommissions.api.commission.export');
    });

    // Current user commissions
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.read.own'))->group(function () {
        Route::get('my-commissions', [DashboardApiController::class, 'getMyCommissions'])
            ->name('rencommissions.api.my-commissions');
        Route::get('my-summary', [DashboardApiController::class, 'getMySummary'])
            ->name('rencommissions.api.my-summary');
    });

    // Reporting endpoints (require report access)
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.read.reports'))->group(function () {
        
        Route::get('earners/{id}/summary', [CommissionApiController::class, 'getEarnerSummary'])
            ->name('rencommissions.api.earner.summary');
    });

    // Admin endpoints (require admin permission)
    Route::middleware(NsRestrictMiddleware::arguments('rencommissions.admin'))->group(function () {
        
        Route::post('cleanup', [CommissionApiController::class, 'runCleanup'])
            ->name('rencommissions.api.cleanup');

        Route::get('session-stats', [CommissionApiController::class, 'getSessionStats'])
            ->name('rencommissions.api.session-stats');
    });
});
