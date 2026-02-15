<?php

namespace Modules\NsMultiStore\Listeners;

use App\Crud\CustomerCrud;
use App\Events\CrudHookEvent;

class CrudHookEventListener
{
    /**
     * Handle the event.
     */
    public function handle( CrudHookEvent $event )
    {
        if ( $event->crud instanceof CustomerCrud ) {
            $event->query->where( 'nexopos_users.origin_store_id', ns()->store->getCurrentStore()->id );
        }
    }
}
