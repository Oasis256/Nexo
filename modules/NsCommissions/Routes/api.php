<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NsCommissions\Http\Controllers\NsCommissionsController;

Route::middleware([
    SubstituteBindings::class,
])->group(function () {
    // Reports
    Route::post('/commissions/reports', [NsCommissionsController::class, 'getReportData']);

    // POS Integration - Commission User Selection
    Route::get('/commissions/eligible-users', [NsCommissionsController::class, 'getEligibleUsers']);
    Route::post('/commissions/preview', [NsCommissionsController::class, 'previewCommission']);
    Route::post('/orders/{order}/products/{orderProduct}/commission-assignment', [NsCommissionsController::class, 'assignCommissionUser']);

    // Commission Product Values (for Fixed type)
    Route::get('/commissions/{commission}/product-values', [NsCommissionsController::class, 'getCommissionProductValues']);
    Route::post('/commissions/{commission}/product-values', [NsCommissionsController::class, 'saveCommissionProductValues']);

    // Product Search for Commission Values
    Route::get('/commissions/products/search', [NsCommissionsController::class, 'searchProducts']);
});
