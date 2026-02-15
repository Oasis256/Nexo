<?php

namespace Modules\RenCommissions\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterEventListener
{
    public function handle(RenderFooterEvent $event): void
    {
        if ($event->routeName !== ns()->routeName('ns.dashboard.pos')) {
            return;
        }

        $event->output->addView('RenCommissions::pos.inject-assets');
    }
}
