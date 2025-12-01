<?php
/**
 * GiftVouchers Module
 * Provides gift voucher management with deferred revenue accounting
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers;

use App\Services\Module;
use Modules\GiftVouchers\Providers\GiftVouchersServiceProvider;

class GiftVouchersModule extends Module
{
    public function __construct()
    {
        parent::__construct(__FILE__);

        // Register the module service provider
        app()->register(GiftVouchersServiceProvider::class);
    }
}