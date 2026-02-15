<?php

namespace Modules\NsCommissions\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterListener
{
    /**
     * Handle the RenderFooterEvent to inject Vue components
     */
    public function handle(RenderFooterEvent $event): void
    {
        // Inject on all pages - the JavaScript will handle when to activate
        $event->output->addOutput(
            view('NsCommissions::partials.vue-components')->render()
        );
    }
}
