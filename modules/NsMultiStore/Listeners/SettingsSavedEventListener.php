<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\SettingsSavedEvent;
use Modules\NsMultiStore\Services\StoresService;
use Modules\NsMultiStore\Settings\GeneralSettings;

class SettingsSavedEventListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return  void
     */
    public function handle(SettingsSavedEvent $event)
    {
        if ($event->settingsClass === GeneralSettings::class && ns()->option->get('nsmultistore-statefuldomains', 'no') === 'yes') {
            /**
             * @var StoresService
             */
            $storesService = app()->make(StoresService::class);
            $storesService->refreshStatefulDomains();

            // will disable the stateful domains option.
            ns()->option->set('nsmultistore-statefuldomains', 'no');
        }
    }
}
