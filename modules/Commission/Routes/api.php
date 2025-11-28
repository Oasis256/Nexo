<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Modules\Commission\Http\Controllers\Api\CommissionApiController;
use Modules\Commission\Http\Controllers\Api\EligibleUsersController;

/**
 * Commission Module API Routes
 * Note: Module API routes are automatically prefixed with 'api/' by ModuleRouting
 */
Route::middleware([
    Authenticate::class,
])->prefix('commissions')->group(function () {
    // Get eligible commission users by category_id query param (for POS popup)
    Route::get('/eligible-users', [EligibleUsersController::class, 'getEligibleUsersByCategory'])
        ->name('commission.api.eligible-users');

    // Get eligible commission users for a specific product
    Route::get('/eligible-users/{product}', [EligibleUsersController::class, 'getEligibleUsers'])
        ->name('commission.api.eligible-users-product');

    // Preview commission calculation
    Route::post('/preview', [CommissionApiController::class, 'preview'])
        ->name('commission.api.preview');

    // Get statistics for widget
    Route::get('/statistics', [CommissionApiController::class, 'getStatistics'])
        ->name('commission.api.statistics');

    // Get top earners
    Route::get('/top-earners', [CommissionApiController::class, 'getTopEarners'])
        ->name('commission.api.top-earners');

    // Get recent commissions
    Route::get('/recent', [CommissionApiController::class, 'getRecentCommissions'])
        ->name('commission.api.recent');

    // Get daily earnings chart data
    Route::get('/daily-earnings', [CommissionApiController::class, 'getDailyEarnings'])
        ->name('commission.api.daily-earnings');

    // Get active commissions for a product
    Route::get('/product/{product}', [CommissionApiController::class, 'getProductCommissions'])
        ->name('commission.api.product-commissions');

    // Get user commission summary
    Route::get('/user/{user}/summary', [CommissionApiController::class, 'getUserSummary'])
        ->name('commission.api.user-summary');

    // Get product values for a commission (CRUD)
    Route::get('/{commission}/product-values', [CommissionApiController::class, 'getProductValues'])
        ->name('commission.api.product-values');

    // Save product values for a commission (CRUD)
    Route::post('/{commission}/product-values', [CommissionApiController::class, 'saveProductValues'])
        ->name('commission.api.save-product-values');

    // Search products for product values (CRUD)
    Route::get('/products/search', [CommissionApiController::class, 'searchProducts'])
        ->name('commission.api.search-products');

    // Store commission assignments from POS
    Route::post('/assignments', [CommissionApiController::class, 'storeAssignments'])
        ->name('commission.api.store-assignments');

    // Get assignments for an order
    Route::get('/assignments/order/{order}', [CommissionApiController::class, 'getOrderAssignments'])
        ->name('commission.api.order-assignments');
});
