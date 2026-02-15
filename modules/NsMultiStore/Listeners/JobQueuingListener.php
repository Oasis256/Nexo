<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Queue\InteractsWithQueue;

class JobQueuingListener
{
    /**
     * Handle the event.
     */
    public function handle( JobQueueing $event )
    {
        // ...
    }
}
