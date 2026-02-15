<?php

namespace Modules\NsMultiStore\Providers;

use App\Classes\Hook;
use App\Crud\CustomerCrud;
use Illuminate\Support\ServiceProvider;
use Modules\NsMultiStore\Events\NsMultiStoreEvent;
use Modules\NsMultiStore\Services\StoresService;

class ModuleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(StoresService::class, fn () => new StoresService);

        ns()->store = app()->make(StoresService::class);

        Hook::addFilter('ns-dashboard-menus', [NsMultiStoreEvent::class, 'dashboardMenus'], 29);
        Hook::addFilter('ns-route', [NsMultiStoreEvent::class, 'builRoute'], 10, 3);
        Hook::addFilter('ns-dashboard-header-file', [NsMultiStoreEvent::class, 'overWriteHeader']);
        Hook::addFilter('ns-url', [NsMultiStoreEvent::class, 'setUrl']);
        Hook::addFilter('ns-asset', [NsMultiStoreEvent::class, 'setAsset']);
        Hook::addFilter('ns-route-name', [NsMultiStoreEvent::class, 'customizeRouteNames']);
        Hook::addFilter('ns-table-name', [NsMultiStoreEvent::class, 'prefixModelTable']);
        Hook::addFilter('ns-common-routes', [NsMultiStoreEvent::class, 'disableDefaultComponents'], 10, 3);
        Hook::addFilter('ns-login-redirect', [NsMultiStoreEvent::class, 'defaultRouteAfterAuthentication'], 10, 2);
        Hook::addFilter('ns-model-table', [NsMultiStoreEvent::class, 'prefixModelTable']);
        Hook::addFilter('ns-reset-table', [NsMultiStoreEvent::class, 'preventTableTruncatingOnMultiStore']);
        Hook::addFilter('ns-media-path', [NsMultiStoreEvent::class, 'changeMediaDirectory']);
        Hook::addFilter('ns-registration-roles', [NsMultiStoreEvent::class, 'filterRoles']);
        Hook::addFilter( CustomerCrud::method( 'filterPostInputs' ), [NsMultiStoreEvent::class, 'customerFilterInputs'], 11 );
        Hook::addFilter( CustomerCrud::method( 'filterPutInputs' ), [NsMultiStoreEvent::class, 'customerFilterInputs'], 11 );
        Hook::addFilter( 'ns-customer-factory', [NsMultiStoreEvent::class, 'customerFactory'], 11 );
        Hook::addFilter( 'ns-cache-prefix', [ NsMultiStoreEvent::class, 'addCachePrefix' ] );
    }

    public function boot()
    {
        // ...
    }
}
