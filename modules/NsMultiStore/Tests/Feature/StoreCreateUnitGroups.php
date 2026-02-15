<?php

namespace Modules\NsMultiStore\Tests\Feature;

use App\Models\Role;
use App\Services\ProductService;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Tests\Feature\CreateUnitGroupTest;

class StoreCreateUnitGroups extends CreateUnitGroupTest
{
    /**
     * @var ProductService
     */
    public $productService;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStoreProducts()
    {
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $this->productService = app()->make(ProductService::class);
        $destinationStore = Store::first();

        Store::switchTo($destinationStore);

        $this->testCreateUnitGroup();
    }
}
