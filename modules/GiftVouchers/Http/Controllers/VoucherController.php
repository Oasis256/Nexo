<?php
/**
 * Voucher Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers;

use App\Http\Controllers\DashboardController;
use Modules\GiftVouchers\Crud\VoucherCrud;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Services\VoucherQrCodeService;

class VoucherController extends DashboardController
{
    /**
     * Display the list of vouchers.
     */
    public function list()
    {
        return VoucherCrud::table();
    }

    /**
     * Display the form to create a new voucher.
     */
    public function create()
    {
        return VoucherCrud::form();
    }

    /**
     * Display the form to edit a voucher.
     */
    public function edit(Voucher $voucher)
    {
        return VoucherCrud::form($voucher);
    }

    /**
     * Display a single voucher details.
     */
    public function view(Voucher $voucher)
    {
        $qrCodeService = app(VoucherQrCodeService::class);
        
        return view('GiftVouchers::vouchers.view', [
            'voucher' => $voucher->load(['template', 'items.product', 'purchaser', 'redemptions.items']),
            'qrBase64' => $qrCodeService->getQrImageBase64($voucher),
            'title' => sprintf(__('Voucher: %s'), $voucher->code),
            'description' => __('View gift voucher details'),
        ]);
    }

    /**
     * Display the printable gift card.
     */
    public function printCard(Voucher $voucher)
    {
        $qrCodeService = app(VoucherQrCodeService::class);
        
        return view('GiftVouchers::vouchers.print-card', [
            'voucher' => $voucher->load(['template', 'purchaser']),
            'qrBase64' => $qrCodeService->getQrImageBase64($voucher),
        ]);
    }
}
