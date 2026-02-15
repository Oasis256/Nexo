<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\AfterHardResetEvent;
use App\Models\Customer;
use App\Services\CustomerService;

class AfterHardResetEventListener
{
    /**
     * Handle the event.
     */
    public function handle( AfterHardResetEvent $event )
    {
        /**
         * 
         */
        $customerService    =   app()->make( CustomerService::class );

        /**
         * We'll delete all the customers 
         * who has been created within that store.
         */
        if ( ns()->store->isMultiStore() ) {
            $ids = Customer::where( 'origin_store_id', ns()->store->getCurrentStore()->id )->get( 'id' );
            foreach ( $ids as $id ) {
                $customerService->delete( $id );
            }
        }
    }
}
