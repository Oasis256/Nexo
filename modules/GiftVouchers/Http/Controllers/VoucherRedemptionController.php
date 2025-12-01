<?php
/**
 * Voucher Redemption Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers;

use App\Http\Controllers\DashboardController;
use Modules\GiftVouchers\Crud\VoucherRedemptionCrud;

class VoucherRedemptionController extends DashboardController
{
    /**
     * Display the list of voucher redemptions.
     */
    public function list()
    {
        return VoucherRedemptionCrud::table();
    }
}
