<?php

namespace Modules\NsCommissions\Providers;

use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\RenderFooterEvent;
use App\Providers\AppServiceProvider;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Event;
use Modules\NsCommissions\Console\Commands\SeedTestDataCommand;
use Modules\NsCommissions\Events\NsCommissionsEvent;
use Modules\NsCommissions\Listeners\RenderFooterListener;
use Modules\NsCommissions\Settings\CommissionsSettings;
use Modules\NsMultiStore\Events\MultiStoreApiRoutesLoadedEvent;
use Modules\NsMultiStore\Events\MultiStoreWebRoutesLoadedEvent;

class ModuleServiceProvider extends AppServiceProvider
{
    /**
     * Console commands provided by the module
     */
    protected $commands = [
        SeedTestDataCommand::class,
    ];

    public function boot()
    {
        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    public function register()
    {
        Hook::addFilter('ns-dashboard-menus', [NsCommissionsEvent::class, 'registerMenus'], 30 );
        Hook::addFilter('ns-crud-resource', [NsCommissionsEvent::class, 'registerCrud']);
        Hook::addFilter('ns.settings', function ($class, $identifier) {
            switch ($identifier) {
                case 'ns.commissions-settings': return new CommissionsSettings; break;
                default: return $class;
            }
        }, 10, 2);

        // Add commission options to POS options
        Hook::addFilter('ns-pos-options', function ($options) {
            $options['ns_commissions_pos_selection'] = ns()->option->get('ns_commissions_pos_selection', 'no');
            $options['ns_commissions_show_preview'] = ns()->option->get('ns_commissions_show_preview', 'yes');
            $options['ns_commissions_allow_on_the_house'] = ns()->option->get('ns_commissions_allow_on_the_house', 'yes');
            
            return $options;
        });

        // Inject Vue components via footer event (works on all pages including POS)
        Event::listen(RenderFooterEvent::class, RenderFooterListener::class);

        Event::listen(MultiStoreApiRoutesLoadedEvent::class, fn () => ModulesService::loadModuleFile('NsCommissions', 'Routes/api'));
        Event::listen(MultiStoreWebRoutesLoadedEvent::class, fn () => ModulesService::loadModuleFile('NsCommissions', 'Routes/multistore'));
        Event::listen(OrderAfterCreatedEvent::class, [NsCommissionsEvent::class, 'trackCommissions']);
        Event::listen(OrderAfterUpdatedEvent::class, [NsCommissionsEvent::class, 'trackCommissions']);
        Event::listen(OrderAfterRefundedEvent::class, [NsCommissionsEvent::class, 'deleteCommissions']);
        Event::listen(OrderBeforeDeleteEvent::class, [NsCommissionsEvent::class, 'deleteCommissions']);
    }
}
