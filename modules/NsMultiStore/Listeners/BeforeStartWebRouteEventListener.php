<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\BeforeStartWebRouteEvent;

class BeforeStartWebRouteEventListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return  void
     */
    public function handle(BeforeStartWebRouteEvent $event)
    {
        if (ns()->store->subDomainsEnabled()) {
            include base_path('modules/NsMultiStore/Routes/subdomains.php');
        }
    }
}
