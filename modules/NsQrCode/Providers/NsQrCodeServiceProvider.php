<?php
/**
 * NsQrCode Service Provider
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\NsQrCode\Services\QrCodeService;
use Modules\NsQrCode\Services\QrKeyService;

class NsQrCodeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register QrCodeService as singleton
        $this->app->singleton(QrCodeService::class, function ($app) {
            return new QrCodeService();
        });

        // Register QrKeyService as singleton
        $this->app->singleton(QrKeyService::class, function ($app) {
            return new QrKeyService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure storage directory exists
        $storagePath = storage_path('app/public/qrcodes');
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
    }
}
