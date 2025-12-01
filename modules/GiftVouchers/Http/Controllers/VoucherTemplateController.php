<?php
/**
 * Voucher Template Controller
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Http\Controllers;

use App\Http\Controllers\DashboardController;
use Modules\GiftVouchers\Crud\VoucherTemplateCrud;
use Modules\GiftVouchers\Models\VoucherTemplate;

class VoucherTemplateController extends DashboardController
{
    /**
     * Display the list of voucher templates.
     */
    public function list()
    {
        return VoucherTemplateCrud::table();
    }

    /**
     * Display the form to create a new voucher template.
     */
    public function create()
    {
        return VoucherTemplateCrud::form();
    }

    /**
     * Display the form to edit a voucher template.
     */
    public function edit(VoucherTemplate $template)
    {
        return VoucherTemplateCrud::form($template);
    }
}
