<?php

use Illuminate\Support\Facades\Route;
use Modules\NsCommissions\Http\Controllers\NsCommissionsController;

Route::get('/reports/commissions', [NsCommissionsController::class, 'getCommsisionsReport'])->name(ns()->routeName('commissions.reports-commissions'));
Route::get('/commissions', [NsCommissionsController::class, 'listCommissions'])->name(ns()->routeName('commissions.list'));
Route::get('/commissions/create', [NsCommissionsController::class, 'createCommissions'])->name(ns()->routeName('commissions.create'));
Route::get('/commissions/edit/{id}', [NsCommissionsController::class, 'updateCommissions'])->name(ns()->routeName('commissions.update'));
Route::get('/users/{user}/commissions', [NsCommissionsController::class, 'usercomissions'])->name(ns()->routeName('users.commissions'));
Route::get('/orders/earned-commissions', [NsCommissionsController::class, 'getEarnedCommissions'])->name(ns()->routeName('earned-commissions.list'));
Route::get('/orders/earned-commissions/create', [NsCommissionsController::class, 'createEarnedCommissions'])->name(ns()->routeName('earned-commissions.create'));
Route::get('/orders/earned-commissions/edit/{id}', [NsCommissionsController::class, 'updateEarnedCommissions'])->name(ns()->routeName('earned-commissions.update'));
