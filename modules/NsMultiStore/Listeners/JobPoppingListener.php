<?php

namespace Modules\NsMultiStore\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobPopping;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Modules\NsMultiStore\Models\Store;

class JobPoppingListener
{
    /**
     * Handle the event.
     */
    public function handle( JobPopping $event )
    {
        // ...
    }
}
