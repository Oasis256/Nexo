<?php
/**
 * GiftVouchers Service Provider
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Providers;

use App\Classes\Hook;
use App\Events\RenderFooterEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\GiftVouchers\Filters\GiftVouchersFilters;
use Modules\GiftVouchers\Listeners\RenderFooterListener;
use Modules\GiftVouchers\Services\VoucherAccountingService;
use Modules\GiftVouchers\Services\VoucherCommissionService;
use Modules\GiftVouchers\Services\VoucherQrCodeService;
use Modules\GiftVouchers\Services\VoucherService;

class GiftVouchersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register menu filters and CRUD handlers
        Hook::addFilter('ns-dashboard-menus', [GiftVouchersFilters::class, 'dashboardMenus'], 30);
        Hook::addFilter('ns-crud-resource', [GiftVouchersFilters::class, 'registerCrud']);
        
        // Inject Vue components via footer event (works on all pages including POS)
        Event::listen(RenderFooterEvent::class, RenderFooterListener::class);

        // Register VoucherAccountingService
        $this->app->singleton(VoucherAccountingService::class, function ($app) {
            return new VoucherAccountingService(
                $app->make(\App\Services\TransactionService::class)
            );
        });

        // Register VoucherCommissionService
        $this->app->singleton(VoucherCommissionService::class, function ($app) {
            return new VoucherCommissionService();
        });

        // Register VoucherQrCodeService
        $this->app->singleton(VoucherQrCodeService::class, function ($app) {
            return new VoucherQrCodeService(
                $app->make(\Modules\NsQrCode\Services\QrCodeService::class),
                $app->make(\Modules\NsQrCode\Services\QrKeyService::class)
            );
        });

        // Register VoucherService
        $this->app->singleton(VoucherService::class, function ($app) {
            return new VoucherService(
                $app->make(VoucherQrCodeService::class),
                $app->make(VoucherAccountingService::class),
                $app->make(VoucherCommissionService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure accounting accounts exist
        if ($this->app->runningInConsole() === false) {
            try {
                $accountingService = $this->app->make(VoucherAccountingService::class);
                $accountingService->ensureAccountsExist();
            } catch (\Throwable $e) {
                // Silently fail during early bootstrap
            }
        }
    }
}
