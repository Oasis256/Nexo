<?php

namespace Modules\NsMultiStore\Listeners;

use App\Models\Role;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\NsMultiStore\Events\StoreAfterUpdatedEvent;
use Modules\NsMultiStore\Services\StoresService;

/**
 * @todo might not be effective
 */
class StoreAfterUpdatedEventListener
{
    /**
     * Handle the event.
     *
     * @param  object $event
     * @return  void
     */
    public function handle( StoreAfterUpdatedEvent $event )
    {
        $storesService  =   app()->make( StoresService::class );
        $rolesID    =   json_decode( $event->store->roles_id );
        $storesService->countRoleStoresUsingID( $rolesID );
    }
}
