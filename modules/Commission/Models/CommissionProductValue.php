<?php

namespace Modules\Commission\Models;

use App\Models\NsModel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionProductValue extends NsModel
{
    protected $table = 'nexopos_commission_product_values';

    protected $fillable = [
        'commission_id',
        'product_id',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:5',
    ];

    /**
     * Commission relationship
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }

    /**
     * Product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
