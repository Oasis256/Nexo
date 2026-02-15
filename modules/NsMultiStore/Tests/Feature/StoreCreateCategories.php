<?php

namespace Modules\NsMultiStore\Tests\Feature;

use App\Models\Role;
use App\Services\ProductService;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Models\Store;
use Tests\Feature\CreateCategoryTest;

class StoreCreateCategories extends CreateCategoryTest
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
    public function testCreateStoreCategories()
    {
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $this->productService = app()->make(ProductService::class);
        $destinationStore = Store::first();

        Store::switchTo($destinationStore);

        $this->testCreateCategory();
    }
}
