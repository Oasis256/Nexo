<?php

namespace Modules\WhatsApp\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterListener
{
    /**
     * Handle the RenderFooterEvent to inject Vue components
     */
    public function handle(RenderFooterEvent $event): void
    {
        $event->output->addOutput(
            view('WhatsApp::partials.vue-components')->render()
        );
    }
}
