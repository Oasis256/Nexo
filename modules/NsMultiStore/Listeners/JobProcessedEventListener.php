<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\InteractsWithQueue;

class JobProcessedEventListener
{
    /**
     * Handle the event.
     */
    public function handle( JobProcessed $event )
    {
        if ( $event->job->resolveName() === 'Modules\NsEmail\Jobs\SendMail' ) {
            // ...
        }
    }
}
