<?php
/**
 * GiftVouchers Web Routes
 * @package GiftVouchers
 */

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ClearRequestCacheMiddleware;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\GiftVouchers\Http\Controllers\VoucherTemplateController;
use Modules\GiftVouchers\Http\Controllers\VoucherController;
use Modules\GiftVouchers\Http\Controllers\VoucherRedemptionController;
use Modules\GiftVouchers\Http\Controllers\VoucherCommissionController;

Route::prefix('dashboard/gift-vouchers')->middleware([
    Authenticate::class,
    ClearRequestCacheMiddleware::class,
])->group(function () {
    // Voucher Templates
    Route::get('/templates', [VoucherTemplateController::class, 'list'])
        ->name('ns.gift-vouchers.templates')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-templates'));
    
    Route::get('/templates/create', [VoucherTemplateController::class, 'create'])
        ->name('ns.gift-vouchers.templates.create')
        ->middleware(NsRestrictMiddleware::arguments('create.gift-voucher-templates'));
    
    Route::get('/templates/edit/{template}', [VoucherTemplateController::class, 'edit'])
        ->name('ns.gift-vouchers.templates.edit')
        ->middleware(NsRestrictMiddleware::arguments('update.gift-voucher-templates'));

    // Vouchers
    Route::get('/vouchers', [VoucherController::class, 'list'])
        ->name('ns.gift-vouchers.vouchers')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::get('/vouchers/create', [VoucherController::class, 'create'])
        ->name('ns.gift-vouchers.vouchers.create')
        ->middleware(NsRestrictMiddleware::arguments('create.gift-vouchers'));
    
    Route::get('/vouchers/edit/{voucher}', [VoucherController::class, 'edit'])
        ->name('ns.gift-vouchers.vouchers.edit')
        ->middleware(NsRestrictMiddleware::arguments('update.gift-vouchers'));
    
    Route::get('/vouchers/view/{voucher}', [VoucherController::class, 'view'])
        ->name('ns.gift-vouchers.vouchers.view')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::get('/vouchers/print/{voucher}', [VoucherController::class, 'printCard'])
        ->name('ns.gift-vouchers.vouchers.print')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));

    // Redemptions
    Route::get('/redemptions', [VoucherRedemptionController::class, 'list'])
        ->name('ns.gift-vouchers.redemptions')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-redemptions'));

    // Commissions
    Route::get('/commissions', [VoucherCommissionController::class, 'list'])
        ->name('ns.gift-vouchers.commissions')
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-commissions'));
});
