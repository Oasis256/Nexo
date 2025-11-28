<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ClearRequestCacheMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\CommissionController;
use Modules\Commission\Http\Controllers\DashboardController;
use Modules\Commission\Http\Controllers\ExportController;

/**
 * Commission Module Web Routes
 * Note: Module web routes get base middleware from ModuleRouting (web, installed, health, migration checks)
 */
Route::middleware([
    Authenticate::class,
    ClearRequestCacheMiddleware::class,
])->prefix('dashboard/commissions')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])
        ->name('commission.dashboard');

    // Commission Rates CRUD
    Route::get('/rates', [CommissionController::class, 'list'])
        ->name('commission.list');

    Route::get('/rates/create', [CommissionController::class, 'create'])
        ->name('commission.create');

    Route::get('/rates/edit/{commission}', [CommissionController::class, 'edit'])
        ->name('commission.edit');

    // Earned Commissions CRUD
    Route::get('/earned', [CommissionController::class, 'earnedList'])
        ->name('commission.earned.list');

    Route::get('/earned/view/{earnedCommission}', [CommissionController::class, 'earnedView'])
        ->name('commission.earned.view');

    // Reports
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->name('commission.reports');

    Route::get('/reports/user/{user}', [DashboardController::class, 'userReport'])
        ->name('commission.reports.user');

    // Exports
    Route::get('/export/csv', [ExportController::class, 'exportCsv'])
        ->name('commission.export.csv');

    Route::get('/export/user-summary', [ExportController::class, 'exportUserSummary'])
        ->name('commission.export.user-summary');

    Route::get('/export/payroll', [ExportController::class, 'exportPayroll'])
        ->name('commission.export.payroll');
});
