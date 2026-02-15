<?php

namespace Modules\NsMultiStore\Listeners;

use App\Models\User;
use App\Services\SetupService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\NsMultiStore\Events\MultiStoreTablesCreatedEvent;
use Modules\NsMultiStore\Services\StoresService;

class MultiStoreTablesCreatedEventListener
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MultiStoreTablesCreatedEvent $event)
    {
        /**
         * @var Setup $setup
         */
        $setup = app()->make(SetupService::class);
        $setup->createDefaultPayment( User::find( $event->store->author ) );

        /**
         * Default Role access for the store.
         * @var StoresService
         */
        $storeService   =   app()->make(StoresService::class);
        $storeService->createDefaultAccessRoles( $event->store );
    }
}
