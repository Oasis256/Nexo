<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NsMultiStore\Http\Controllers\MultiStoreController;

Route::middleware([
    'auth:sanctum',
    SubstituteBindings::class,
])
    ->group(function () {
        Route::get('/multistores/stores', [MultiStoreController::class, 'getStores']);
        Route::get('/multistores/{store}/reinstall', [MultiStoreController::class, 'reInstall']);
        Route::get('/multistores/{store}/rebuild', [MultiStoreController::class, 'rebuildStore']);
        Route::post('/multistore/migrate/{id}', [MultiStoreController::class, 'runMigration'])
            ->name('ns.multistore.run-migration');
        Route::get('/multistores/stores-details', [MultiStoreController::class, 'getStoreDetails']);
    });

/**
 * Let's register what should be the perfect
 * api route according to subdomain settings.
 */
if (ns()->store->subDomainsEnabled()) {
    require dirname(__FILE__).'/api-subdomains.php';
} else {
    require dirname(__FILE__).'/api-regular.php';
}
