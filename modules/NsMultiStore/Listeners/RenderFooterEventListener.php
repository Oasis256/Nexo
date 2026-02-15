<?php
namespace Modules\NsMultiStore\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterEventListener
{
    public function handle( RenderFooterEvent $event )
    {
        if ( ns()->store->isMultiStore() ) {
            $event->output->addView( 'NsMultiStore::dashboard.footer-http-overrider' );
        }
    }
}