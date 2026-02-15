<?php

use App\Http\Controllers\Dashboard\UsersController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\NsMultiStore\Events\MultiStoreApiRoutesLoadedEvent;
use Modules\NsMultiStore\Http\Middleware\DetectStoreMiddleware;

Route::prefix('/store/{store_id}')
    ->middleware([
        DetectStoreMiddleware::class.':api',
        SubstituteBindings::class,
    ])
    ->group(function () {
        ns()->store->defineStoreRoutes(function () {
            include base_path('routes/api/fields.php');

            Route::middleware([
                'auth:sanctum',
            ])->group(function () {
                include base_path('routes/api/dashboard.php');
                include base_path('routes/api/categories.php');
                include base_path('routes/api/customers.php');
                include base_path('routes/api/transactions.php');
                include base_path('routes/api/medias.php');
                include base_path('routes/api/notifications.php');
                include base_path('routes/api/orders.php');
                include base_path('routes/api/procurements.php');
                include base_path('routes/api/products.php');
                include base_path('routes/api/providers.php');
                include base_path('routes/api/registers.php');
                include base_path('routes/api/reports.php');
                include base_path('routes/api/reset.php');
                include base_path('routes/api/settings.php');
                include base_path('routes/api/rewards.php');
                include base_path('routes/api/taxes.php');
                include base_path('routes/api/crud.php');
                include base_path('routes/api/forms.php');
                include base_path('routes/api/units.php');

                Route::post( '/users/widgets', [ UsersController::class, 'configureWidgets' ] );
            });

            MultiStoreApiRoutesLoadedEvent::dispatch();
        });
    });
