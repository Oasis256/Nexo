<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Skeleton\Http\Controllers\SkeletonController;

Route::prefix('skeleton')->group(function () {
    // Get items with pagination and search
    Route::get('/items', [SkeletonController::class, 'getItemsApi'])
        ->name('skeleton.api.items')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.read.items'));

    // Submit custom action
    Route::post('/action', [SkeletonController::class, 'submitAction'])
        ->name('skeleton.api.action')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.create.items'));
});
