<?php

namespace Modules\NsCommissions\Models;

use App\Models\NsModel;
use App\Models\ProductCategory;

class CommissionProductCategory extends NsModel
{
    protected $table = 'nexopos_commissions_products_categories';

    public $timestamps = false;

    public function category()
    {
        return $this->hasOne(ProductCategory::class, 'id', 'category_id');
    }
}
