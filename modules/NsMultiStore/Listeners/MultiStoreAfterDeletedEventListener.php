<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\NsMultiStore\Events\MultiStoreAfterDeletedEvent;
use Modules\NsMultiStore\Services\StoresService;

class MultiStoreAfterDeletedEventListener
{
    /**
     * Handle the event.
     *
     * @param  object $event
     * @return  void
     */
    public function handle( MultiStoreAfterDeletedEvent $event )
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
        $storesService->removeCreatedRoles( $event->store );
        $storesService->removeAllowedRoles( $event->store );
    }
}
