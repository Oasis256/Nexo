<?php

namespace Modules\NsMultiStore\Providers;

use App\Classes\Hook;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ProvidersEventServiceProvider;
use Modules\NsMultiStore\Events\NsMultiStoreEvent;
use Modules\NsMultiStore\Models\User as ModelsUser;

class EventServiceProvider extends ProvidersEventServiceProvider
{
    public function register()
    {
        Hook::addFilter('ns.settings', [NsMultiStoreEvent::class, 'registerSettings'], 10, 2);

        /**
         * We want to make sure every where the User model is requested
         * this custom version is provided that filters user per store.
         */
        app()->bind(User::class, ModelsUser::class);
    }

    public function boot()
    {
        // ...
    }
}
