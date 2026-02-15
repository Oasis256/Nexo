<?php

use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\OutputMiddleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NsMultiStore\Events\MultiStorePublicRoutesLoadedEvent;
use Modules\NsMultiStore\Events\MultiStoreWebRoutesLoadedEvent;
use Modules\NsMultiStore\Http\Controllers\MultiStoreController;
use Modules\NsMultiStore\Http\Middleware\CheckModuleMigrationMiddleware;
use Modules\NsMultiStore\Http\Middleware\CheckStoreAccessMiddleware;
use Modules\NsMultiStore\Http\Middleware\DetectStoreMiddleware;
use Modules\NsMultiStore\Http\Middleware\MultiStoreAuthenticate;

$domain = pathinfo(env('APP_URL'));
$fullDomain = $domain['filename'].(isset($domain['extension']) ? '.'.$domain['extension'] : '');

Route::domain('{substore}.'.$fullDomain)->group(function () {
    Route::middleware([
        DetectStoreMiddleware::class,
        CheckModuleMigrationMiddleware::class,
        SubstituteBindings::class,
        // OutputMiddleware::class,
    ])->group(function () {
        Route::get('', [MultiStoreController::class, 'subDomainHome']);

        ns()->store->defineStoreRoutes(function () {

            /**
             * We would like the store detector to applies on
             * the authentication routes, so that we can clearly
             * perform actions related to the visited store.
             */
            require base_path().'/routes/authenticate.php';

            /**
             * Let's load all the default dashboard routes
             */
            Route::middleware([
                MultiStoreAuthenticate::class,
                CheckApplicationHealthMiddleware::class,
                CheckStoreAccessMiddleware::class,
            ])->group(function () {
                Route::prefix('dashboard')->group(function () {
                    Route::get('/users/profile', [UsersController::class, 'getProfile'])->name(ns()->routeName('ns.dashboard.users.profile'));
                    Route::get('/users', [MultiStoreController::class, 'listUsers'])->name(ns()->routeName('ns.multistore-users.list'));
                    Route::get('/users/edit/{user}', [MultiStoreController::class, 'editUser'])->name(ns()->routeName('ns.multistore-users.edit'));
                    Route::get('/users/create', [MultiStoreController::class, 'createUser'])->name(ns()->routeName('ns.multistore-users.create'));

                    require base_path().'/routes/nexopos.php';

                    event(new MultiStoreWebRoutesLoadedEvent);
                });
            });

            MultiStorePublicRoutesLoadedEvent::dispatch();
        });
    });
});
