<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\CheckMigrationStatus;
use App\Http\Middleware\OutputMiddleware;
use App\Http\Middleware\InstalledStateMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NsMultiStore\Events\MultiStoreWebRoutesLoadedEvent;
use Modules\NsMultiStore\Http\Controllers\MultiStoreController;
use Modules\NsMultiStore\Http\Middleware\CheckModuleMigrationMiddleware;
use Modules\NsMultiStore\Http\Middleware\DetectStoreMiddleware;
use Modules\NsMultiStore\Http\Middleware\ProtectMultistoreRootMiddleware;

$domain = pathinfo(env('APP_URL'));
$fullDomain = $domain['filename'].(isset($domain['extension']) ? '.'.$domain['extension'] : '');

/**
 * This should only be included if
 * we're using the root domain.
 */
Route::domain($fullDomain)->group(function ($foo) {
    Route::prefix('dashboard')->group(function () {
        Route::middleware([
            CheckApplicationHealthMiddleware::class,
            InstalledStateMiddleware::class,
        ])->group(function () {
            Route::middleware([
                CheckMigrationStatus::class,
                SubstituteBindings::class,
                Authenticate::class,
            ])->group(function () {
                Route::middleware([
                    ProtectMultistoreRootMiddleware::class,
                ])->group(function () {
                    Route::get('/multistore/settings', [MultiStoreController::class, 'settings'])->name('ns.multistore-settings');
                    Route::get('/multistore/stores', [MultiStoreController::class, 'stores'])->name('ns.multistore-stores');
                    Route::get('/multistore/stores/create', [MultiStoreController::class, 'create'])->name('ns.multistore-stores.create');
                    Route::get('/multistore/stores/edit/{store}', [MultiStoreController::class, 'edit'])->name('ns.multistore-stores.edit');
                    Route::get('/multistore', [MultiStoreController::class, 'home'])->name('ns.multistore-dashboard');
                });

                Route::get('/multistore/migrate/{store}', [MultiStoreController::class, 'migrateStore'])->name('ns.multistore-migrate');
                Route::get('/multistore/select', [MultiStoreController::class, 'selectStore'])->name('ns.multistore-select');
            });

            /**
             * If the subdomain routing is not enabled
             * we'll use the default detecting mecanism from the URL segments.
             */
            if (ns()->option->get('nsmultistore-subdomain', 'disabled') === 'disabled') {
                Route::prefix('/store/{store_id}')
                    ->middleware([
                        DetectStoreMiddleware::class.':web',
                        SubstituteBindings::class,
                        Authenticate::class,
                        CheckModuleMigrationMiddleware::class,
                        // OutputMiddleware::class,
                    ])
                    ->group(function () {
                        ns()->store->defineStoreRoutes(function () {
                            require base_path().'/routes/nexopos.php';
                            event(new MultiStoreWebRoutesLoadedEvent);
                        });
                    });
            }
        });
    });
});
