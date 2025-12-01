<?php
/**
 * GiftVouchers API Routes
 * @package GiftVouchers
 */

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;
use Modules\GiftVouchers\Http\Controllers\Api\VoucherTemplateApiController;
use Modules\GiftVouchers\Http\Controllers\Api\VoucherApiController;
use Modules\GiftVouchers\Http\Controllers\Api\VoucherRedemptionApiController;

Route::prefix('gift-vouchers')->middleware([
    Authenticate::class,
])->group(function () {
    // Voucher Templates API
    Route::get('/templates', [VoucherTemplateApiController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-templates'));
    
    Route::post('/templates', [VoucherTemplateApiController::class, 'store'])
        ->middleware(NsRestrictMiddleware::arguments('create.gift-voucher-templates'));
    
    Route::get('/templates/{template}', [VoucherTemplateApiController::class, 'show'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-templates'));
    
    Route::put('/templates/{template}', [VoucherTemplateApiController::class, 'update'])
        ->middleware(NsRestrictMiddleware::arguments('update.gift-voucher-templates'));
    
    Route::delete('/templates/{template}', [VoucherTemplateApiController::class, 'destroy'])
        ->middleware(NsRestrictMiddleware::arguments('delete.gift-voucher-templates'));

    // Vouchers API
    Route::get('/', [VoucherApiController::class, 'index'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::post('/', [VoucherApiController::class, 'store'])
        ->middleware(NsRestrictMiddleware::arguments('create.gift-vouchers'));
    
    Route::get('/{voucher}', [VoucherApiController::class, 'show'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::put('/{voucher}', [VoucherApiController::class, 'update'])
        ->middleware(NsRestrictMiddleware::arguments('update.gift-vouchers'));
    
    Route::delete('/{voucher}', [VoucherApiController::class, 'destroy'])
        ->middleware(NsRestrictMiddleware::arguments('delete.gift-vouchers'));

    // Voucher lookup by code or QR key
    Route::get('/lookup/code/{code}', [VoucherApiController::class, 'lookupByCode'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::post('/lookup/qr', [VoucherApiController::class, 'lookupByQrKey'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));

    // QR Code operations
    Route::get('/{voucher}/qr', [VoucherApiController::class, 'getQrCode'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));
    
    Route::post('/{voucher}/qr/regenerate', [VoucherApiController::class, 'regenerateQr'])
        ->middleware(NsRestrictMiddleware::arguments('regenerate.gift-voucher-qr'));

    // Cancel voucher
    Route::post('/{voucher}/cancel', [VoucherApiController::class, 'cancel'])
        ->middleware(NsRestrictMiddleware::arguments('cancel.gift-vouchers'));

    // Redemption API
    Route::post('/{voucher}/redeem', [VoucherRedemptionApiController::class, 'redeem'])
        ->middleware(NsRestrictMiddleware::arguments('redeem.gift-vouchers'));
    
    Route::get('/{voucher}/redemptions', [VoucherRedemptionApiController::class, 'getForVoucher'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-voucher-redemptions'));

    // Statistics
    Route::get('/statistics/summary', [VoucherApiController::class, 'statistics'])
        ->middleware(NsRestrictMiddleware::arguments('read.gift-vouchers'));

    // POS Integration endpoints
    Route::get('/{voucher}/cart-items', [VoucherApiController::class, 'getCartItems'])
        ->middleware(NsRestrictMiddleware::arguments('redeem.gift-vouchers'));
    
    Route::post('/pos/lookup', [VoucherApiController::class, 'posLookup'])
        ->middleware(NsRestrictMiddleware::arguments('redeem.gift-vouchers'));
});
