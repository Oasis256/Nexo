<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\InteractsWithQueue;

class JobProcessingListener
{
    /**
     * Handle the event.
     */
    public function handle( JobProcessing $event )
    {
        // ...
    }
}
