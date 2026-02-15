<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\JobAfterUnserializeEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\NsMultiStore\Models\Store;

class JobAfterUnserializeEventListener
{
    /**
     * Handle the event.
     *
     * @param  object $event
     * @return  void
     */
    public function handle( JobAfterUnserializeEvent $event )
    {
        /**
         * We can safely detect the store used while dispatching
         * the job and make sure it's defined as the selected store.
         */
        if ( isset( $event->job->store ) && $event->job->store instanceof Store ) {
            ns()->store->setStore( $event->job->store );
        }

        if ( isset( $event->job->attributes ) ) {
            foreach( $event->job->attributes as $name => $params ) {
                /**
                 * We'll retrieve what is the primary key
                 */
                $primary        =   ( new $params->class )->getKeyName();

                /**
                 * Now we'll rebuild the model
                 * by refreshing that.
                 */
                $event->job->$name   =   $params->class::findOrFail( $params->object->$primary );
            }
        }

        $event;
    }
}
