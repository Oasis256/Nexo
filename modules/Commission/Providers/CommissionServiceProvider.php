<?php

namespace Modules\Commission\Providers;

use App\Classes\Hook;
use App\Events\RenderFooterEvent;
use App\Services\ModulesService;
use App\Services\SettingsPage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Commission\Events\CommissionEvent;
use Modules\Commission\Listeners\RenderFooterListener;
use Modules\Commission\Services\CommissionCalculatorService;
use Modules\Commission\Services\CommissionExportService;
use Modules\Commission\Services\CommissionReportService;
use Modules\Commission\Settings\CommissionSettings;

class CommissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the calculator service as singleton
        $this->app->singleton(CommissionCalculatorService::class, function ($app) {
            return new CommissionCalculatorService();
        });

        // Register the report service
        $this->app->singleton(CommissionReportService::class, function ($app) {
            return new CommissionReportService();
        });

        // Register the export service
        $this->app->singleton(CommissionExportService::class, function ($app) {
            return new CommissionExportService($app->make(CommissionReportService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(ModulesService $modulesService): void
    {
        // Register views
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'Commission');

        // Register Vue components via RenderFooterEvent
        Event::listen(RenderFooterEvent::class, RenderFooterListener::class);

        // Register dashboard menus
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            return CommissionEvent::registerMenus($menus);
        });

        // Note: CRUD resources are auto-discovered via AUTOLOAD constant in Crud classes
        // No manual hook registration needed for CommissionCrud and EarnedCommissionCrud

        // Register settings (with 2 arguments: $class, $identifier)
        Hook::addFilter('ns.settings', function ($class, $identifier) {
            if ($identifier === CommissionSettings::IDENTIFIER) {
                return new CommissionSettings();
            }
            return $class;
        }, 10, 2);

        // Hook into order processing to track commissions
        Hook::addAction('ns-order-paid', function ($order) {
            CommissionEvent::trackCommissions($order);
        });

        // Hook into order refunds to handle commission adjustments
        Hook::addAction('ns-order-refunded', function ($order) {
            CommissionEvent::deleteCommissions($order);
        });

        // Hook into order voiding
        Hook::addAction('ns-order-voided', function ($order) {
            CommissionEvent::deleteCommissions($order);
        });

        // Register widgets if on dashboard
        Hook::addFilter('ns-dashboard-widgets', function ($widgets) {
            $widgets[] = \Modules\Commission\Widgets\TotalEarningsWidget::class;
            $widgets[] = \Modules\Commission\Widgets\TopEarnersWidget::class;
            $widgets[] = \Modules\Commission\Widgets\RecentCommissionsWidget::class;
            return $widgets;
        });
    }
}
