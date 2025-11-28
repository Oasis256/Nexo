<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Modules\MyNexoPOS\Http\Controllers\MyNexoPOSController;

Route::middleware([
    SubstituteBindings::class,
])->group(function () {
    Route::post('/mns/select-license', [MyNexoPOSController::class, 'applySelectedLicense']);
    Route::get('/mns/core/latest-release', [MyNexoPOSController::class, 'getCoreLatestRelease']);
    Route::post('/mns/core/update', [MyNexoPOSController::class, 'proceedCoreUpdate']);
    Route::get('/mns/disconnect', [MyNexoPOSController::class, 'disconnect']);
    Route::post('/mns/vendor-installation', [MyNexoPOSController::class, 'installVendor'])->name('mynexopos.vendor-installation');
});
