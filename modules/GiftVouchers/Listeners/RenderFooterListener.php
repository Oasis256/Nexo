<?php

namespace Modules\GiftVouchers\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterListener
{
    /**
     * Handle the RenderFooterEvent to inject Vue components for POS integration
     */
    public function handle(RenderFooterEvent $event): void
    {
        // Inject on all pages - the JavaScript will handle when to activate
        $event->output->addOutput(
            view('GiftVouchers::partials.vue-components')->render()
        );
    }
}
