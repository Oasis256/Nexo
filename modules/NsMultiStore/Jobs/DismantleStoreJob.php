<?php

namespace Modules\NsMultiStore\Jobs;

use App\Models\Role;
use App\Services\NotificationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;
use Throwable;

class DismantleStoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $store;

    public function __construct($store)
    {
        $this->store = (object) $store;
    }

    public function handle(
        StoresService $storeService,
    ) {
        if (
            in_array($this->store->status, [
                Store::STATUS_DISMANTLING,
            ])
        ) {
            $store = Store::find($this->store->id);
            $storeService->dismantleStore($store);
            
            /**
             * The notification should be dispatched 
             * at the root of the application
             */
            $storeService->unsetStore();

            return ns()->notification->create(
                title: __m('Store Dismantling Status', 'NsMultiStore'),
                description: sprintf(__m('The Store "%s" has been successfully dismantled..', 'NsMultiStore'), $this->store->name),
                url: url('/dashboard/multistores/stores'),
            )->dispatchForGroup([
                Role::namespace('admin'),
                Role::namespace('nexopos.store.administrator'),
            ]);
        }

        throw new Exception(sprintf(__m('Wrong status for dismantling a store %s', 'NsMultiStore'), $this->store->status));
    }

    public function failed(Throwable $exception)
    {
        /**
         * @var StoresService
         */
        $storesService = app()->make( StoresService::class );

        /**
         * We want the notification to be dispatched at the root level
         */
        $storesService->unsetStore();

        $store  =   Store::find($this->store->id);
        $store->status = Store::STATUS_FAILED;
        $store->save();

        return ns()->notification->create(
            title: __m('Store Dismantlement Failed', 'NsMultiStore'),
            description: sprintf(__m('The Store "%s" cannot be dismantled. Error : %s.', 'NsMultiStore'), $this->store->name, $exception->getMessage()),
            url: url('/dashboard/multistore/stores'),
        )->dispatchForGroup([
            Role::namespace('admin'),
            Role::namespace('nexopos.store.administrator'),
        ]);
    }
}
