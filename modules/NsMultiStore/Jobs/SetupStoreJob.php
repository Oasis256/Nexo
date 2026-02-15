<?php

namespace Modules\NsMultiStore\Jobs;

use App\Classes\Hook;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;
use Throwable;

class SetupStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * This should only be performed once
     */
    public $tries = 1;

    public function __construct( public Store $store)
    {
        // ...
    }

    public function handle( StoresService $storesService, NotificationService $notificationService )
    {
        if ($this->store->status === Store::STATUS_BUILDING) {

            $storesService->createStoreTables($this->store);

            $this->store->status = Store::STATUS_OPENED;
            $this->store->save();

            /**
             * The notification should be dispatched
             * at the root installation.
             */
            $storesService->unsetStore();

            $notificationService->create([
                'title'         =>  __m('Store Crafting Status', 'NsMultiStore'),
                'description'   =>  sprintf(__m('The store "%s" has been successfully built. It\'s awaiting to be used.', 'NsMultiStore'), $this->store->name),
                'url'           =>  url('/dashboard/store/'.$this->store->id),
            ])->dispatchForGroup([
                Role::namespace('admin'),
                Role::namespace('nexopos.store.administrator'),
            ]);
        }
    }

    public function failed(Throwable $exception)
    {        
        /**
         * @var StoresService
         */
        $storeService = app()->make(StoresService::class);
        $storeService->unsetStore();

        /**
         * @var NotificationService
         */
        $notificationService = app()->make(NotificationService::class);

        $notificationService->create([
            'title'         =>  __m('Store Creation Failed', 'NsMultiStore'),
            'description'   =>  sprintf(
                __m('Unable to complete the mantling of the store %s. The request has returned this message : %s', 'NsMultiStore'),
                $this->store->name,
                $exception->getMessage()
            ),
            'url'           =>  url('/dashboard/store/'.$this->store->id),
        ])->dispatchForGroup([
            Role::namespace('admin'),
            Role::namespace('nexopos.store.administrator'),
        ]);

        $this->store->status = Store::STATUS_FAILED;
        $this->store->save();
    }
}
