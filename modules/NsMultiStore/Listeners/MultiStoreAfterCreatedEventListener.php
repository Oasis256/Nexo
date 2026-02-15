<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\NsMultiStore\Events\MultiStoreAfterCreatedEvent;
use Modules\NsMultiStore\Services\StoresService;

class MultiStoreAfterCreatedEventListener
{
    /**
     * Handle the event.
     *
     * @param  object $event
     * @return  void
     */
    public function handle( MultiStoreAfterCreatedEvent $event )
    {
        /**
         * @var StoresService
         */
        $storesService  =   app()->make( StoresService::class );

        /**
         * We'll refresh the store count
         * for the deleted store roles.
         */
        $storesService->countRoleStoresUsingID( json_decode( $event->store->roles_id ) );
    }
}
