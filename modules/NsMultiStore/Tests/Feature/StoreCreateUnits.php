<?php

namespace Modules\NsMultiStore\Tests\Feature;

use App\Models\Role;
use App\Models\Unit;
use App\Services\ProductService;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Tests\Feature\CreateUnitTest;

class StoreCreateUnits extends CreateUnitTest
{
    protected $execute = false;

    /**
     * @var ProductService
     */
    public $productService;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStoreUnits()
    {
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $this->productService = app()->make(ProductService::class);
        $destinationStore = Store::first();

        Store::switchTo($destinationStore);

        Unit::truncate();

        $this->execute = true;
        $this->testCreateUnits();
        $this->execute = false;
    }
}
