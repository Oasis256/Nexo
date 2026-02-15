<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\CustomerModelBootedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CustomerModelBootedEventListener
{
    /**
     * Handle the event.
     */
    public function handle( CustomerModelBootedEvent $event )
    {
        if ( ns()->store->isMultiStore() ) {
            $event->builder->where( 'nexopos_users.origin_store_id', ns()->store->getCurrentStore()->id );
        }
    }
}
