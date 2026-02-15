<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class JobQueuedListener
{
    /**
     * Handle the event.
     */
    public function handle( JobQueued $event )
    {
        $payload    =   $event->payload();
        $uuid       =   $payload['uuid'] ?? null;

        if ( $uuid ) {
            $additionalPayload  =   [
                'store' =>  ns()->store->getCurrentStore()
            ];
            
            Cache::set( 'nsm-payload-' . $uuid, $additionalPayload, 60 );
        }
    }
}
