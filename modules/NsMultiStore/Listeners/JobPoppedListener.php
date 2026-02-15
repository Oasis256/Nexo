<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Queue\Events\JobPopped;
use Illuminate\Support\Facades\Cache;
use Modules\NsMultiStore\Events\MultiStoreJobHijackEvent;
use Modules\NsMultiStore\Models\Store;

class JobPoppedListener
{
    /**
     * Handle the event.
     */
    public function handle( JobPopped $event )
    {
        $payload    =   $event->job->payload();
        $uuid       =   $payload['uuid'];

        if ( ! $uuid ) {
            return;
        }
        
        $data       =   Cache::get( 'nsm-payload-' . $uuid );

        /**
         * If the data is not found in the cache
         * we're probably not handling a multi store job
         */
        if ( isset( $data[ 'store' ] ) ) {
            $store      =   Store::find( $data[ 'store' ][ 'id' ] );
    
            /**
             * If the store is not found, we're probably handly a job for
             * a store that has been deleted. We'll just ignore it.
             */
            if ( ! $store instanceof Store ) {
                return;
            }
    
            ns()->store->setStore( $store );
    
            /**
             * Dispatch event to notify that the job has been hijacked
             * by the multi store system
             */
            MultiStoreJobHijackEvent::dispatch();
        }
    }
}
