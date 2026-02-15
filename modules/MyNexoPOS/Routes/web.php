<?php

use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\MyNexoPOS\Http\Controllers\MyNexoPOSController;
use Modules\MyNexoPOS\Http\Middleware\CheckHasLicenseAssignedMiddleware;
use Modules\MyNexoPOS\Http\Middleware\CheckHasTokenMiddleware;
use Modules\MyNexoPOS\Http\Middleware\CheckIfHasLicenseAssignedMiddleware;
use Modules\MyNexoPOS\Http\Middleware\CheckPlatformAuthenticationMiddleware;

Route::middleware([
    SubstituteBindings::class,
])->group(function () {
    Route::get('mns-modules-vendor', [MyNexoPOSController::class, 'installModulesVendor'])
        ->name('mynexopos.modules-vendors')
        ->withoutMiddleware([
            CheckApplicationHealthMiddleware::class,
        ]);
});

Route::middleware([
    SubstituteBindings::class,
    Authenticate::class,
    NsRestrictMiddleware::arguments( 'mns.update-system' ),
])->group(function () {
    Route::prefix('dashboard')->group(function () {
        Route::get('mns/update', [MyNexoPOSController::class, 'updaterPage'])->name('mynexopos.update')->middleware([
            CheckPlatformAuthenticationMiddleware::class,
            CheckHasLicenseAssignedMiddleware::class,
            CheckHasTokenMiddleware::class,
        ]);

        Route::get('mns/deactivate-license', [MyNexoPOSController::class, 'deactivateLicense'])
            ->middleware([
                CheckPlatformAuthenticationMiddleware::class,
                CheckHasLicenseAssignedMiddleware::class,
                CheckHasTokenMiddleware::class,
            ])
            ->name('mynexopos.deactivate-license');

        Route::get('mns/callback', [MyNexoPOSController::class, 'verifyAuthentication'])->name('mynexopos.verify-authentication');
        Route::get('mns/update/authentify', [MyNexoPOSController::class, 'authentify'])->name('mynexopos.authentify');
        Route::get('mns/select-license', [MyNexoPOSController::class, 'selectLicense'])
            ->middleware([
                CheckIfHasLicenseAssignedMiddleware::class,
                CheckPlatformAuthenticationMiddleware::class,
                CheckHasTokenMiddleware::class,
            ])
            ->name('mynexopos.select-license');
    });

    Route::get('oauth/my.nexopos.com', [MyNexoPOSController::class, 'requestToken'])->name('mynexopos.authorization');
});
