<?php
/**
 * Voucher Commission Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers;

use App\Http\Controllers\DashboardController;
use Modules\GiftVouchers\Crud\VoucherCommissionCrud;

class VoucherCommissionController extends DashboardController
{
    /**
     * Display the list of voucher commissions.
     */
    public function list()
    {
        return VoucherCommissionCrud::table();
    }
}
