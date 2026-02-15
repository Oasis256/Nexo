<?php

namespace Modules\NsMultiStore\Listeners;

use App\Events\AfterSuccessfulLoginEvent;
use App\Exceptions\NotAllowedException;
use Illuminate\Support\Facades\Auth;
use Modules\NsMultiStore\Services\StoresService;

class AfterSuccessfulLoginEventListener
{
    public function handle(AfterSuccessfulLoginEvent $event)
    {
        try {
            /**
             * @var StoresService
             */
            $storeService = app()->make(StoresService::class);
            $storeService->checkStoreAccessibility();
        } catch (NotAllowedException $exception) {
            Auth::logout();
            throw new NotAllowedException($exception->getMessage());
        }
    }
}
