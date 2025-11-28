<?php

use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\Skeleton\Http\Controllers\SkeletonController;

Route::prefix('dashboard/skeleton')->group(function () {
    // Main dashboard
    Route::get('/', [SkeletonController::class, 'index'])
        ->name('skeleton.dashboard')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.read.items'));

    // Items CRUD routes
    Route::get('/items', [SkeletonController::class, 'listItems'])
        ->name('skeleton.items.list')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.read.items'));

    Route::get('/items/create', [SkeletonController::class, 'createItem'])
        ->name('skeleton.items.create')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.create.items'));

    Route::get('/items/edit/{item}', [SkeletonController::class, 'editItem'])
        ->name('skeleton.items.edit')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.update.items'));

    // Features page
    Route::get('/features', [SkeletonController::class, 'featuresPage'])
        ->name('skeleton.features')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.read.items'));

    // Settings page
    Route::get('/settings', [SkeletonController::class, 'settings'])
        ->name('skeleton.settings')
        ->middleware(NsRestrictMiddleware::arguments('skeleton.update.items'));
});
