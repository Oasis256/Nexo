<?php

namespace Modules\WhatsApp\Providers;

use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;
use Modules\WhatsApp\Filters\WhatsAppFilters;

class WhatsAppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CRUD resources filter (required for ns-crud component to find CRUD classes)
        Hook::addFilter('ns-crud-resource', [WhatsAppFilters::class, 'registerCrud'], 10);
        
        // Register menu filters using class-based handlers (avoids closure serialization issues)
        Hook::addFilter('ns-dashboard-menus', [WhatsAppFilters::class, 'dashboardMenus'], 25);
        Hook::addFilter('ns.settings', [WhatsAppFilters::class, 'settingsPages'], 10, 2);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register views
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'WhatsApp');
    }
}
