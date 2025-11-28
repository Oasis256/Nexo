<?php

namespace Modules\MyNexoPOS\Providers;

use App\Classes\Hook;
use App\Events\AfterAppHealthCheckedEvent;
use App\Providers\AppServiceProvider as CoreProvider;
use Illuminate\Support\Facades\Event;
use Modules\MyNexoPOS\Events\MyNexoPOSFilters;
use Modules\MyNexoPOS\Services\UpdateService;
use Modules\MyNexoPOS\Settings\MyNexoPOSSettings;

class ModuleServiceProvider extends CoreProvider
{
    public function boot()
    {
    }

    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', [MyNexoPOSFilters::class, 'dashboardMenus'], 50);

        Hook::addFilter('ns.settings', function ($class, $identifier) {
            switch ($identifier) {
                case MyNexoPOSSettings::$namespace : return new MyNexoPOSSettings; break;
                default: return $class;
            }
        }, 10, 2);

        Event::listen(AfterAppHealthCheckedEvent::class, function () {
            /**
             * @var UpdateService $updateService
             */
            $updateService = app()->make(UpdateService::class);
            $updateService->checkModulesVendor();
        });
    }
}
