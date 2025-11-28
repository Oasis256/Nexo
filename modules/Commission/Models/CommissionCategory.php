<?php

namespace Modules\Commission\Models;

use App\Models\NsModel;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionCategory extends NsModel
{
    protected $table = 'nexopos_commission_categories';

    protected $fillable = [
        'commission_id',
        'category_id',
    ];

    /**
     * Commission relationship
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }

    /**
     * Product Category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'id');
    }
}
