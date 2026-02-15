<?php

namespace Modules\NsMultiStore\Tests\Feature;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Jobs\DismantleStoreJob;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;
use Tests\TestCase;

class StoreCreateStores extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $storesNames = ['Store Test A', 'Store Test B'];

        /**
         * @var StoresService
         */
        $storeService = app()->make(StoresService::class);
        $stores = Store::whereIn('name', $storesNames)->get();
        $stores->each(function ($store) use ($storeService) {
            $storeService->dismantleStore($store);
        });

        foreach ($storesNames as $storeName) {
            $store = Store::where('slug', Str::slug($storeName))->first();

            if ($store instanceof Store) {
                $store->status = Store::STATUS_DISMANTLING;
                $store->save();

                DismantleStoreJob::dispatchSync($store);
            }

            $response = $this->withSession($this->app['session']->all())
                ->json('POST', 'api/crud/ns.multistore', [
                    'name'          =>  $storeName,
                    'general'       =>  [
                        'status'        =>  Store::STATUS_OPENED,
                        'roles_id'      =>  Role::get()->map(fn ($role) => $role->id)->toArray(),
                    ],
                ]);

            $response->assertJsonPath('status', 'success');
        }
    }
}
