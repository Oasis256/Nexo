<?php
namespace Modules\NsMultiStore\Listeners;

use App\Events\RenderFooterEvent;
use App\Events\RenderHeaderEvent;

class RenderHeaderEventListener
{
    public function handle( RenderHeaderEvent $event )
    {
        $event->output->addView('NsMultiStore::dashboard.header-style');
    }
}