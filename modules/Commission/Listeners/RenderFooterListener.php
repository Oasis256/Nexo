<?php

namespace Modules\Commission\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterListener
{
    /**
     * Handle the RenderFooterEvent to inject Vue components
     */
    public function handle(RenderFooterEvent $event): void
    {
        // Inject Vue components on all pages
        // The JavaScript handles when components should activate
        $event->output->addOutput(
            view('Commission::partials.vue-components')->render()
        );
    }
}
