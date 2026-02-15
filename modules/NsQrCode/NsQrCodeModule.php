<?php
/**
 * NsQrCode Module
 * Provides QR code generation and secure key signing services
 * @package NsQrCode
 */

namespace Modules\NsQrCode;

use App\Services\Module;
use Modules\NsQrCode\Providers\NsQrCodeServiceProvider;

class NsQrCodeModule extends Module
{
    public function __construct()
    {
        parent::__construct(__FILE__);

        // Register the module service provider
        app()->register(NsQrCodeServiceProvider::class);
    }
}